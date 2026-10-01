<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuizQuestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_quiz_updates_existing_questions_without_deleting_answers(): void
    {
        $admin = $this->adminUser();
        $quiz = Quiz::create(['name' => 'Original quiz']);
        $question = $quiz->questions()->create(['text' => 'Original question', 'order' => 1]);
        $answer = $question->answers()->create([
            'text' => 'Existing answer',
            'points' => 2,
            'meta' => ['target_type' => 'member', 'target_id' => 17],
        ]);

        $this->actingAs($admin)
            ->put(route('admin.quizzes.update', $quiz->id), [
                'name' => 'Updated quiz',
                'questions' => ['Updated question'],
                'question_ids' => [$question->id],
            ])
            ->assertRedirect(route('admin.quizzes.index'));

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'quiz_id' => $quiz->id,
            'text' => 'Updated question',
        ]);
        $this->assertDatabaseHas('answers', [
            'id' => $answer->id,
            'question_id' => $question->id,
            'text' => 'Existing answer',
        ]);
        $this->assertSame(['target_type' => 'member', 'target_id' => 17], $answer->fresh()->meta);
    }

    public function test_new_question_is_not_created_until_non_empty_text_is_submitted(): void
    {
        $admin = $this->adminUser();
        $quiz = Quiz::create(['name' => 'Question creation quiz']);

        $this->actingAs($admin)
            ->get(route('admin.quizzes.questions.create', $quiz->id))
            ->assertOk()
            ->assertSee('Question text');
        $this->assertSame(0, $quiz->questions()->count());

        $this->post(route('admin.quizzes.questions.store', $quiz->id), ['text' => ''])
            ->assertSessionHasErrors('text');
        $this->assertSame(0, $quiz->questions()->count());

        $this->post(route('admin.quizzes.questions.store', $quiz->id), ['text' => 'A real question'])
            ->assertRedirect();
        $this->assertDatabaseHas('questions', [
            'quiz_id' => $quiz->id,
            'text' => 'A real question',
            'order' => 1,
        ]);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::create(['name' => 'admin', 'label' => 'Administrator']));

        return $user;
    }
}