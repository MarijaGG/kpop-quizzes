<?php

namespace Tests\Feature;

use App\Models\GuessIdolImage;
use App\Models\Group;
use App\Models\Member;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuessIdolTest extends TestCase
{
    use RefreshDatabase;

    public function test_guess_idol_round_uses_distinct_images_and_saves_once(): void
    {
        $groupId = Group::create(['name' => 'Test Group'])->id;
        $memberIds = collect(range(1, 4))->map(fn ($number) => Member::create([
            'group_id' => $groupId,
            'name' => 'Test Member '.$number,
        ])->id);
        $user = User::factory()->create();

        for ($index = 0; $index < 5; $index++) {
            GuessIdolImage::create([
                'group_id' => $groupId,
                'member_id' => $memberIds[$index % $memberIds->count()],
                'difficulty' => 'medium',
                'image' => 'images/guess-idol/test-'.$index.'.jpg',
            ]);
        }

        $this->actingAs($user)
            ->post(route('guess-idol.start'), ['group_id' => $groupId, 'difficulty' => 'medium'])
            ->assertRedirect(route('guess-idol.take'));

        $run = session('guess_idol_run');
        $this->assertCount(5, $run['questions']);
        $this->assertCount(5, collect($run['questions'])->pluck('image_id')->unique());
        $this->assertTrue(collect($run['questions'])->every(fn ($question) => count($question['options']) === 4));

        foreach ($run['questions'] as $question) {
            $this->post(route('guess-idol.answer'), ['choice' => $question['correct_member_id']]);
        }

        $this->assertDatabaseHas('quiz_results', [
            'user_id' => $user->id,
            'result_type' => 'guess_idol',
            'correct_answers' => 5,
            'total_questions' => 5,
        ]);
        $this->assertSame(1, QuizResult::where('user_id', $user->id)->where('result_type', 'guess_idol')->count());

        $this->get(route('guess-idol.result'))->assertOk();
        $this->assertSame(1, QuizResult::where('user_id', $user->id)->where('result_type', 'guess_idol')->count());
    }
}
