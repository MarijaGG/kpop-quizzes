<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Group;
use App\Models\Member;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizPublicationAndRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_quizzes_start_as_drafts_and_are_not_publicly_listed(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('admin.quizzes.store'), [
            'name' => 'Draft quiz',
            'questions' => ['First question', '', '   ', '', '', '', '', '', '', ''],
        ]);

        $quiz = Quiz::where('name', 'Draft quiz')->firstOrFail();
        $this->assertFalse($quiz->is_published);
        $this->assertSame(1, $quiz->questions()->count());

        $this->actingAs(User::factory()->create())
            ->get(route('quizzes.index'))
            ->assertOk()
            ->assertDontSee('Draft quiz');
        $this->assertEquals(route('admin.quizzes.edit', $quiz->id), $response->headers->get('Location'));
    }

    public function test_incomplete_quiz_cannot_be_published(): void
    {
        $admin = $this->adminUser();
        $quiz = Quiz::create(['name' => 'Incomplete quiz', 'is_published' => false]);
        $question = $quiz->questions()->create(['text' => 'Question?', 'order' => 1]);
        Answer::create(['question_id' => $question->id, 'text' => 'Only answer', 'points' => 0]);

        $this->actingAs($admin)
            ->put(route('admin.quizzes.update', $quiz->id), [
                'name' => $quiz->name,
                'is_published' => '1',
            ])
            ->assertSessionHasErrors('is_published');

        $this->assertFalse($quiz->fresh()->is_published);
    }

    public function test_complete_quiz_can_be_published_and_added_questions_unpublish_it(): void
    {
        $admin = $this->adminUser();
        $group = Group::create(['name' => 'Publish group']);
        $member = Member::create(['group_id' => $group->id, 'name' => 'Publish member']);
        $quiz = Quiz::create([
            'name' => 'Ready quiz',
            'group_id' => $group->id,
            'is_published' => false,
        ]);

        foreach (range(1, 10) as $order) {
            $question = $quiz->questions()->create(['text' => "Question {$order}?", 'order' => $order]);
            foreach (['First', 'Second'] as $label) {
                Answer::create([
                    'question_id' => $question->id,
                    'text' => "{$label} answer {$order}",
                    'points' => 0,
                    'meta' => ['target_type' => 'member', 'target_id' => $member->id],
                ]);
            }
        }

        $this->actingAs($admin)
            ->put(route('admin.quizzes.update', $quiz->id), [
                'name' => $quiz->name,
                'group_id' => $group->id,
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.quizzes.index'));
        $this->assertTrue($quiz->fresh()->is_published);

        $this->post(route('admin.quizzes.questions.store', $quiz->id), ['text' => 'A new question?'])
            ->assertRedirect();
        $this->assertFalse($quiz->fresh()->is_published);
    }

    public function test_admin_cannot_assign_a_member_from_another_group_to_a_quiz(): void
    {
        $admin = $this->adminUser();
        $groupOne = Group::create(['name' => 'First group']);
        $groupTwo = Group::create(['name' => 'Second group']);
        $member = Member::create(['group_id' => $groupTwo->id, 'name' => 'Other group member']);

        $this->actingAs($admin)
            ->post(route('admin.quizzes.store'), [
                'name' => 'Mismatched quiz',
                'group_id' => $groupOne->id,
                'member_id' => $member->id,
                'questions' => array_fill(0, 10, ''),
            ])
            ->assertSessionHasErrors('member_id');

        $this->assertDatabaseMissing('quizzes', ['name' => 'Mismatched quiz']);
    }

    public function test_static_api_rejects_member_and_group_mismatch_for_quiz_create_and_update(): void
    {
        $admin = $this->adminUser();
        $groupOne = Group::create(['name' => 'API first group']);
        $groupTwo = Group::create(['name' => 'API second group']);
        $member = Member::create(['group_id' => $groupTwo->id, 'name' => 'API other group member']);
        $quiz = Quiz::create(['name' => 'API quiz', 'group_id' => $groupOne->id]);

        $this->actingAs($admin)
            ->postJson('/api/quizzes', [
                'name' => 'Invalid API quiz',
                'group_id' => $groupOne->id,
                'member_id' => $member->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('member_id');

        $this->actingAs($admin)
            ->patchJson("/api/quizzes/{$quiz->id}", ['member_id' => $member->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('member_id');
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::create(['name' => 'admin', 'label' => 'Administrator']));

        return $user;
    }
}
