<?php

namespace Tests\Feature;

use App\Models\GuessSong;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuessSongAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_replaying_a_concurrent_final_answer_only_saves_one_result(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 5) as $number) {
            GuessSong::create([
                'title' => "Song {$number}",
                'artist' => 'Test Artist',
                'audio' => "audio/song-{$number}.mp3",
            ]);
        }

        $this->actingAs($user)
            ->post(route('guess-song.start'))
            ->assertRedirect(route('guess-song.take'));
        $run = session('guess_song_run');
        $this->assertNotEmpty($run['attempt_key']);

        $preFinalRun = null;
        for ($round = 0; $round < 5; $round++) {
            $this->post(route('guess-song.answer'), ['skip' => '1'])->assertRedirect(route('guess-song.take'));
            $this->post(route('guess-song.answer'), ['skip' => '1'])->assertRedirect(route('guess-song.take'));

            if ($round === 4) {
                $preFinalRun = session('guess_song_run');
            }

            $response = $this->post(route('guess-song.answer'), ['skip' => '1']);
            $response->assertRedirect($round === 4
                ? route('guess-song.result')
                : route('guess-song.take'));
        }

        $this->assertSame(1, QuizResult::where('user_id', $user->id)->where('result_type', 'guess_song')->count());
        $this->withSession(['guess_song_run' => $preFinalRun])
            ->post(route('guess-song.answer'), ['skip' => '1'])
            ->assertRedirect(route('guess-song.result'));

        $results = QuizResult::where('user_id', $user->id)->where('result_type', 'guess_song')->get();
        $this->assertCount(1, $results);
        $this->assertSame($run['attempt_key'], $results->first()->attempt_key);
        $this->assertSame(0, $results->first()->total_points);
    }
}
