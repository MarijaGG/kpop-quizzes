<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_perfect_niki_quiz_score_unlocks_a_selectable_title(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::create(['name' => "How Well Do You Know ENHYPEN's Ni-ki?"]);
        QuizResult::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'quiz_name' => $quiz->name,
            'result_type' => 'knowledge',
            'correct_answers' => 10,
            'total_questions' => 10,
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'selected_title' => 'niki-number-one-fan',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');
        $this->assertSame('niki-number-one-fan', $user->refresh()->selected_title);
    }

    public function test_unearned_titles_cannot_be_selected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/profile/edit')
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'selected_title' => 'hyunjin-number-one-fan',
            ]);

        $response->assertSessionHasErrors('selected_title')->assertRedirect('/profile/edit');
        $this->assertNull($user->refresh()->selected_title);
    }

    public function test_perfect_quiz_title_is_awarded_only_once(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::create(['name' => "How Well Do You Know ENHYPEN's Ni-ki?"]);

        $firstAward = $user->awardTitleForPerfectQuiz($quiz, 10, 10);
        $secondAward = $user->awardTitleForPerfectQuiz($quiz, 10, 10);

        $this->assertNotNull($firstAward);
        $this->assertNull($secondAward);
        $this->assertSame(1, $user->titleAwards()->where('title_key', 'niki-number-one-fan')->count());
    }

    public function test_awarded_title_can_be_equipped_from_quiz_result_action(): void
    {
        $user = User::factory()->create();
        $user->titleAwards()->create([
            'title_key' => 'hyunjin-number-one-fan',
            'awarded_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch('/profile/title', [
            'title' => 'hyunjin-number-one-fan',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');
        $this->assertSame('hyunjin-number-one-fan', $user->refresh()->selected_title);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
