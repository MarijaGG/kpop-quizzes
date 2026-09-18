<?php

namespace Tests\Feature;

use App\Models\Quiz;
use Database\Seeders\ApiDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_quiz_questions_are_seeded_with_answers(): void
    {
        $this->seed(ApiDataSeeder::class);

        $quiz = Quiz::with('questions.answers')->findOrFail(1);

        $this->assertSame(10, $quiz->questions->count());
        $this->assertTrue($quiz->questions->every(fn ($question) => $question->answers->isNotEmpty()));
    }
}