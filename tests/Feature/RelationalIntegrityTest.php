<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GuessIdolImage;
use App\Models\Member;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationalIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_member_or_group_removes_guess_idol_images(): void
    {
        $group = Group::create(['name' => 'Integrity group']);
        $member = Member::create(['group_id' => $group->id, 'name' => 'Integrity member']);
        $image = GuessIdolImage::create([
            'group_id' => $group->id,
            'member_id' => $member->id,
            'difficulty' => 'easy',
            'image' => 'images/test.jpg',
        ]);

        $member->delete();
        $this->assertDatabaseMissing('guess_idol_images', ['id' => $image->id]);

        $member = Member::create(['group_id' => $group->id, 'name' => 'Second member']);
        $image = GuessIdolImage::create([
            'group_id' => $group->id,
            'member_id' => $member->id,
            'difficulty' => 'easy',
            'image' => 'images/test-2.jpg',
        ]);
        $group->delete();

        $this->assertDatabaseMissing('guess_idol_images', ['id' => $image->id]);
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    }

    public function test_deleting_quiz_and_user_anonymizes_result_references(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::create(['name' => 'Result quiz']);
        $result = QuizResult::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'quiz_name' => 'Result quiz',
            'result_type' => 'knowledge',
            'correct_answers' => 8,
            'total_questions' => 10,
            'total_points' => 80,
        ]);

        $quiz->delete();
        $this->assertDatabaseHas('quiz_results', ['id' => $result->id, 'quiz_id' => null]);

        $user->delete();
        $this->assertDatabaseHas('quiz_results', ['id' => $result->id, 'user_id' => null]);
    }
}
