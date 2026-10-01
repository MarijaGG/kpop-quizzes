<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Group;
use App\Models\Member;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reloading_a_quiz_result_does_not_increment_statistics_again(): void
    {
        $user = User::factory()->create();
        $group = Group::create(['name' => 'Test Group']);
        $member = Member::create(['group_id' => $group->id, 'name' => 'Test Member']);
        $otherMember = Member::create(['group_id' => $group->id, 'name' => 'Other Member']);
        $quiz = Quiz::create(['name' => 'Test personality quiz']);
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'text' => 'Choose one',
            'order' => 1,
        ]);
        $answer = Answer::create([
            'question_id' => $question->id,
            'text' => 'Test answer',
            'meta' => ['target_type' => 'member', 'target_id' => $member->id],
        ]);
        Answer::create([
            'question_id' => $question->id,
            'text' => 'Other answer',
            'meta' => ['target_type' => 'member', 'target_id' => $otherMember->id],
        ]);

        $this->actingAs($user)
            ->get(route('quizzes.start', $quiz->id))
            ->assertRedirect(route('quizzes.take', $quiz->id));
        $this->get(route('quizzes.take', $quiz->id))->assertOk();

        $this->post(route('quizzes.answer', $quiz->id), ['choice' => $answer->id])
            ->assertRedirect(route('quizzes.result', $quiz->id));

        $this->get(route('quizzes.result', $quiz->id))->assertOk();
        $this->get(route('quizzes.result', $quiz->id))->assertOk();
        $this->get(route('quizzes.result', $quiz->id))->assertOk();

        $this->assertSame(1, QuizResult::where('user_id', $user->id)->where('quiz_id', $quiz->id)->count());
        $this->assertNotNull(QuizResult::where('user_id', $user->id)->where('quiz_id', $quiz->id)->value('attempt_key'));
        $this->assertSame(1, $quiz->fresh()->settings['quiz_stats']['member'][(string) $member->id]);
    }

    public function test_personality_title_cannot_be_earned_before_answering_every_question(): void
    {
        $user = User::factory()->create();
        $group = Group::create(['name' => 'Complete Quiz Group']);
        $member = Member::create(['group_id' => $group->id, 'name' => 'Complete Quiz Member']);
        $quiz = Quiz::create(['name' => 'Complete quiz for a title']);
        $answers = [];

        foreach ([1, 2] as $order) {
            $question = Question::create([
                'quiz_id' => $quiz->id,
                'text' => "Question {$order}",
                'order' => $order,
            ]);
            $answers[] = Answer::create([
                'question_id' => $question->id,
                'text' => "Member answer {$order}",
                'meta' => ['target_type' => 'member', 'target_id' => $member->id],
            ]);
        }

        $this->actingAs($user)
            ->get(route('quizzes.start', $quiz->id))
            ->assertRedirect(route('quizzes.take', $quiz->id));
        $this->get(route('quizzes.take', $quiz->id))->assertOk();
        $this->post(route('quizzes.answer', $quiz->id), ['choice' => $answers[0]->id])
            ->assertRedirect(route('quizzes.take', $quiz->id));

        $this->get(route('quizzes.result', $quiz->id))
            ->assertRedirect(route('quizzes.take', $quiz->id));
        $this->assertSame(0, QuizResult::where('user_id', $user->id)->where('quiz_id', $quiz->id)->count());
        $this->assertSame(0, $user->titleAwards()->count());
        $this->assertEmpty($quiz->fresh()->settings['quiz_stats'] ?? []);

        $this->get(route('quizzes.take', $quiz->id))->assertOk();
        $this->post(route('quizzes.answer', $quiz->id), ['choice' => $answers[1]->id])
            ->assertRedirect(route('quizzes.result', $quiz->id));
        $this->get(route('quizzes.result', $quiz->id))->assertOk();

        $this->assertSame(1, QuizResult::where('user_id', $user->id)->where('quiz_id', $quiz->id)->count());
        $this->assertSame(1, $user->titleAwards()->count());
        $this->assertSame(1, $quiz->fresh()->settings['quiz_stats']['member'][(string) $member->id]);
    }
}