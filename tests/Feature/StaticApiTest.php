<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Group;
use App\Models\Member;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users_cannot_write_to_the_static_api(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/groups', ['name' => 'Unauthorized Group'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('groups', ['name' => 'Unauthorized Group']);
    }

    public function test_admin_users_can_create_a_group_with_validated_attributes(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin', 'label' => 'Administrator']));

        $this->actingAs($admin)
            ->postJson('/api/groups', [
                'name' => 'Validated Group',
                'unexpected' => 'must not be persisted',
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Validated Group')
            ->assertJsonMissingPath('unexpected');

        $this->assertDatabaseHas('groups', ['name' => 'Validated Group']);
    }

    public function test_invalid_relationships_are_rejected_by_resource_validation(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin', 'label' => 'Administrator']));

        $this->actingAs($admin)
            ->postJson('/api/members', [
                'group_id' => 999999,
                'name' => 'Invalid Member',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['group_id']);
    }

    public function test_non_admin_reads_do_not_expose_quiz_scoring_internals(): void
    {
        $user = User::factory()->create();
        $group = Group::create(['name' => 'API public group']);
        $member = Member::create(['group_id' => $group->id, 'name' => 'API public member']);
        $quiz = Quiz::create([
            'name' => 'Internals quiz',
            'group_id' => $group->id,
            'settings' => ['quiz_stats' => ['member' => [(string) $member->id => 5]]],
        ]);
        foreach (range(1, 10) as $order) {
            $question = Question::create([
                'quiz_id' => $quiz->id,
                'text' => "Question {$order}?",
                'order' => $order,
                'meta' => ['secret' => true],
            ]);
            foreach (['Answer A', 'Answer B'] as $text) {
                Answer::create([
                    'question_id' => $question->id,
                    'text' => "{$text} {$order}",
                    'points' => 5,
                    'meta' => ['target_type' => 'member', 'target_id' => $member->id],
                ]);
            }
        }

        $this->actingAs($user)->getJson('/api/answers')->assertForbidden();
        $this->getJson('/api/questions')->assertForbidden();
        $quizzes = $this->getJson('/api/quizzes')->assertOk()->json('data');
        $this->assertNotEmpty($quizzes);
        foreach ($quizzes as $item) {
            $this->assertArrayNotHasKey('settings', $item);
        }
    }

    public function test_admin_reads_still_include_scoring_internals(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin', 'label' => 'Administrator']));
        $quiz = Quiz::create(['name' => 'Admin quiz', 'settings' => ['quiz_stats' => []]]);
        $question = Question::create(['quiz_id' => $quiz->id, 'text' => 'Question?', 'order' => 1]);
        Answer::create(['question_id' => $question->id, 'text' => 'Answer', 'points' => 3, 'meta' => ['target_type' => 'member', 'target_id' => 7]]);

        $answers = $this->actingAs($admin)->getJson('/api/answers')->assertOk()->json('data');

        $this->assertSame('member', $answers[0]['target_type']);
        $this->assertSame(7, $answers[0]['target_id']);
        $this->assertSame(3, $answers[0]['points']);
    }

    public function test_public_api_hides_draft_and_incomplete_quiz_content(): void
    {
        $user = User::factory()->create();
        $group = Group::create(['name' => 'Visibility group']);
        $member = Member::create(['group_id' => $group->id, 'name' => 'Visibility member']);

        $draft = Quiz::create(['name' => 'Secret draft', 'is_published' => false]);
        $draftQuestion = $draft->questions()->create(['text' => 'Secret draft question?', 'order' => 1]);
        $draftAnswer = $draftQuestion->answers()->create(['text' => 'Secret draft answer', 'points' => 0]);

        $incomplete = Quiz::create(['name' => 'Incomplete published quiz', 'is_published' => true]);
        $incompleteQuestion = $incomplete->questions()->create(['text' => 'Incomplete question?', 'order' => 1]);
        $incompleteQuestion->answers()->create(['text' => 'Incomplete answer', 'points' => 0]);

        $public = Quiz::create([
            'name' => 'Public ready quiz',
            'group_id' => $group->id,
            'is_published' => true,
        ]);
        foreach (range(1, 10) as $order) {
            $question = $public->questions()->create(['text' => "Public question {$order}?", 'order' => $order]);
            foreach (['A', 'B'] as $choice) {
                $question->answers()->create([
                    'text' => "{$choice} public answer {$order}",
                    'points' => 0,
                    'meta' => ['target_type' => 'member', 'target_id' => $member->id],
                ]);
            }
        }

        $this->actingAs($user)
            ->getJson('/api/quizzes')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Public ready quiz'])
            ->assertJsonMissing(['name' => 'Secret draft'])
            ->assertJsonMissing(['name' => 'Incomplete published quiz']);
        $this->getJson('/api/questions')->assertForbidden();
        $this->getJson('/api/answers')->assertForbidden();

        $this->getJson("/api/quizzes/{$draft->id}")->assertNotFound();
        $this->getJson("/api/questions/{$draftQuestion->id}")->assertForbidden();
        $this->getJson("/api/answers/{$draftAnswer->id}")->assertForbidden();
        $this->getJson('/api')->assertOk()
            ->assertJsonFragment(['api' => 'database'])
            ->assertJsonMissing(['text' => 'Secret draft question?']);
    }

    public function test_static_api_rejects_invalid_or_cross_group_answer_targets(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin', 'label' => 'Administrator']));
        $quizGroup = Group::create(['name' => 'Answer quiz group']);
        $otherGroup = Group::create(['name' => 'Answer other group']);
        $otherMember = Member::create(['group_id' => $otherGroup->id, 'name' => 'Wrong group member']);
        $quiz = Quiz::create(['name' => 'Answer target quiz', 'group_id' => $quizGroup->id]);
        $question = $quiz->questions()->create(['text' => 'Target question?', 'order' => 1]);

        $this->actingAs($admin)
            ->postJson('/api/answers', [
                'question_id' => $question->id,
                'text' => 'Cross group target',
                'target_type' => 'member',
                'target_id' => $otherMember->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('meta.target_id');

        $this->postJson('/api/answers', [
            'question_id' => $question->id,
            'text' => 'Missing target',
            'target_type' => 'member',
            'target_id' => 999999,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('meta.target_id');
    }

    public function test_api_answer_writes_unpublish_the_parent_quiz_and_keep_existing_targets_on_text_update(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin', 'label' => 'Administrator']));
        $group = Group::create(['name' => 'Answer mutation group']);
        $member = Member::create(['group_id' => $group->id, 'name' => 'Answer mutation member']);
        $quiz = Quiz::create(['name' => 'Published before API write', 'group_id' => $group->id]);
        $question = $quiz->questions()->create(['text' => 'Mutation question?', 'order' => 1]);

        $answerResponse = $this->actingAs($admin)
            ->postJson('/api/answers', [
                'question_id' => $question->id,
                'text' => 'Targeted answer',
                'target_type' => 'member',
                'target_id' => $member->id,
            ])
            ->assertCreated();
        $answerId = $answerResponse->json('id');
        $this->assertFalse($quiz->fresh()->is_published);

        $this->putJson("/api/answers/{$answerId}", ['text' => 'Renamed answer'])
            ->assertOk();
        $this->assertSame(
            ['target_type' => 'member', 'target_id' => $member->id],
            Answer::findOrFail($answerId)->meta,
        );
    }

    public function test_resource_lists_are_filtered_and_paginated_with_a_maximum_page_size(): void
    {
        $user = User::factory()->create();
        $groupA = Group::create(['name' => 'Paged group A']);
        $groupB = Group::create(['name' => 'Paged group B']);
        foreach (range(1, 3) as $number) {
            Member::create(['group_id' => $groupA->id, 'name' => "Paged member {$number}"]);
        }
        Member::create(['group_id' => $groupB->id, 'name' => 'Other group member']);

        $page = $this->actingAs($user)
            ->getJson("/api/members?group_id={$groupA->id}&per_page=2")
            ->assertOk()
            ->assertJsonPath('per_page', 2)
            ->assertJsonPath('total', 3)
            ->assertJsonPath('last_page', 2);
        $this->assertCount(2, $page->json('data'));
        $this->assertTrue(collect($page->json('data'))->every(fn ($member) => $member['group_id'] === $groupA->id));

        $this->getJson('/api/members?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');

        $this->getJson('/api')->assertJsonFragment(['resources' => ['groups', 'members', 'albums', 'quizzes', 'questions', 'answers']]);
    }
}
