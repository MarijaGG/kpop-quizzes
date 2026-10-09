<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Answer;
use App\Models\Group;
use App\Models\Member;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\User;
use App\Services\QuizResultDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_result_data_service_loads_only_quiz_entities_and_members_of_target_groups(): void
    {
        $group = Group::create(['name' => 'Target Group']);
        $otherGroup = Group::create(['name' => 'Unrelated Group']);
        $member = Member::create(['group_id' => $group->id, 'name' => 'Target Member']);
        $groupMate = Member::create(['group_id' => $group->id, 'name' => 'Group Mate']);
        Member::create(['group_id' => $otherGroup->id, 'name' => 'Unrelated Member']);
        $album = Album::create(['group_id' => $group->id, 'title' => 'Target Album']);
        Album::create(['group_id' => $otherGroup->id, 'title' => 'Unrelated Album']);

        $entities = app(QuizResultDataService::class)->forRun([
            'questions' => [[
                'answers' => [
                    ['target_type' => 'member', 'target_id' => $member->id],
                    ['target_type' => 'album', 'target_id' => $album->id],
                ],
            ]],
        ]);

        $this->assertEqualsCanonicalizing(
            [$member->id, $groupMate->id],
            $entities['members']->modelKeys()
        );
        $this->assertSame([$group->id], $entities['groups']->modelKeys());
        $this->assertSame([$album->id], $entities['albums']->modelKeys());
    }

    public function test_result_page_handles_a_member_deleted_during_an_active_attempt(): void
    {
        $user = User::factory()->create();
        $group = Group::create(['name' => 'Temporary Group']);
        $member = Member::create(['group_id' => $group->id, 'name' => 'Deleted During Quiz']);
        $quiz = Quiz::create(['name' => 'Member personality quiz']);
        $questions = [];
        $responses = [];

        foreach (range(1, 10) as $questionId) {
            $answerId = 1000 + $questionId;
            $questions[] = [
                'id' => $questionId,
                'answers' => [[
                    'id' => $answerId,
                    'text' => 'Member answer',
                    'target_type' => 'member',
                    'target_id' => $member->id,
                ]],
            ];
            $responses[] = $answerId;
        }

        $member->delete();

        $this->actingAs($user)
            ->withSession([
                "quiz_run.{$quiz->id}" => [
                    'quiz' => [
                        'quiz_id' => $quiz->id,
                        'attempt_key' => 'deleted-member-attempt',
                        'questions' => $questions,
                    ],
                    'index' => 10,
                    'responses' => $responses,
                ],
            ])
            ->get(route('quizzes.result', $quiz->id))
            ->assertOk()
            ->assertViewHas('resultType', null)
            ->assertViewHas('result', null);

        $this->assertSame(0, QuizResult::where('attempt_key', 'deleted-member-attempt')->count());
    }

    public function test_reloading_a_quiz_result_does_not_increment_statistics_again(): void
    {
        $user = User::factory()->create();
        $group = Group::create(['name' => 'Test Group']);
        $member = Member::create(['group_id' => $group->id, 'name' => 'Test Member']);
        $otherMember = Member::create(['group_id' => $group->id, 'name' => 'Other Member']);
        $quiz = Quiz::create(['name' => 'Test personality quiz']);
        $answers = [];
        foreach (range(1, 10) as $order) {
            $question = Question::create([
                'quiz_id' => $quiz->id,
                'text' => "Choose one {$order}",
                'order' => $order,
            ]);
            $answers[] = Answer::create([
                'question_id' => $question->id,
                'text' => "Test answer {$order}",
                'meta' => ['target_type' => 'member', 'target_id' => $member->id],
            ]);
            Answer::create([
                'question_id' => $question->id,
                'text' => "Other answer {$order}",
                'meta' => ['target_type' => 'member', 'target_id' => $otherMember->id],
            ]);
        }

        $this->actingAs($user)
            ->get(route('quizzes.start', $quiz->id))
            ->assertRedirect(route('quizzes.take', $quiz->id));
        $this->get(route('quizzes.take', $quiz->id))->assertOk();

        foreach ($answers as $index => $answer) {
            $response = $this->post(route('quizzes.answer', $quiz->id), [
                'choice' => $this->currentAnswerId($quiz->id, $member->id),
            ]);
            $response->assertRedirect($index === 9
                ? route('quizzes.result', $quiz->id)
                : route('quizzes.take', $quiz->id));
        }

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

        foreach (range(1, 10) as $order) {
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
            Answer::create([
                'question_id' => $question->id,
                'text' => "Alternate answer {$order}",
                'meta' => ['target_type' => 'member', 'target_id' => $member->id],
            ]);
        }

        $this->actingAs($user)
            ->get(route('quizzes.start', $quiz->id))
            ->assertRedirect(route('quizzes.take', $quiz->id));
        $this->get(route('quizzes.take', $quiz->id))->assertOk();
        $this->post(route('quizzes.answer', $quiz->id), [
            'choice' => $this->currentAnswerId($quiz->id, $member->id),
        ])
            ->assertRedirect(route('quizzes.take', $quiz->id));

        $this->get(route('quizzes.result', $quiz->id))
            ->assertRedirect(route('quizzes.take', $quiz->id));
        $this->assertSame(0, QuizResult::where('user_id', $user->id)->where('quiz_id', $quiz->id)->count());
        $this->assertSame(0, $user->titleAwards()->count());
        $this->assertEmpty($quiz->fresh()->settings['quiz_stats'] ?? []);

        $this->get(route('quizzes.take', $quiz->id))->assertOk();
        foreach (range(1, 9) as $index) {
            $response = $this->post(route('quizzes.answer', $quiz->id), [
                'choice' => $this->currentAnswerId($quiz->id, $member->id),
            ]);
            $response->assertRedirect($index === 9
                ? route('quizzes.result', $quiz->id)
                : route('quizzes.take', $quiz->id));
        }
        $this->get(route('quizzes.result', $quiz->id))->assertOk();

        $this->assertSame(1, QuizResult::where('user_id', $user->id)->where('quiz_id', $quiz->id)->count());
        $this->assertSame(1, $user->titleAwards()->count());
        $this->assertSame(1, $quiz->fresh()->settings['quiz_stats']['member'][(string) $member->id]);
    }

    private function currentAnswerId(int $quizId, int $memberId): int
    {
        $state = session("quiz_run.{$quizId}");
        $question = $state['quiz']['questions'][$state['index']];

        return (int) collect($question['answers'])
            ->first(fn (array $answer) => (int) $answer['target_id'] === $memberId)['id'];
    }
}
