<?php

namespace Tests\Feature;

use App\Models\Answer;
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
        $quiz = Quiz::create(['name' => 'Internals quiz', 'settings' => ['quiz_stats' => ['member' => ['1' => 5]]]]);
        $question = Question::create(['quiz_id' => $quiz->id, 'text' => 'Question?', 'order' => 1, 'meta' => ['secret' => true]]);
        Answer::create(['question_id' => $question->id, 'text' => 'Answer', 'points' => 5, 'meta' => ['target_type' => 'member', 'target_id' => 1]]);

        $answers = $this->actingAs($user)->getJson('/api/answers')->assertOk()->json();
        $questions = $this->actingAs($user)->getJson('/api/questions')->assertOk()->json();
        $quizzes = $this->actingAs($user)->getJson('/api/quizzes')->assertOk()->json();

        foreach ($answers as $answer) {
            $this->assertArrayNotHasKey('meta', $answer);
            $this->assertArrayNotHasKey('points', $answer);
            $this->assertArrayNotHasKey('target_type', $answer);
            $this->assertArrayNotHasKey('target_id', $answer);
        }
        foreach ($questions as $item) {
            $this->assertArrayNotHasKey('meta', $item);
        }
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

        $answers = $this->actingAs($admin)->getJson('/api/answers')->assertOk()->json();

        $this->assertSame('member', $answers[0]['target_type']);
        $this->assertSame(7, $answers[0]['target_id']);
        $this->assertSame(3, $answers[0]['points']);
    }
}
