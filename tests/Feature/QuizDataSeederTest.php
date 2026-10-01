<?php

namespace Tests\Feature;

use App\Models\Quiz;
use Database\Seeders\ApiDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuizDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_seed_data_quizzes_questions_and_answers_are_imported_with_valid_references(): void
    {
        $data = json_decode(file_get_contents(resource_path('data/api.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->seed(ApiDataSeeder::class);

        $quizzes = Quiz::with('questions.answers')->get()->keyBy('id');

        $this->assertCount(count($data['quizzes']), $quizzes);
        $this->assertSame(count($data['questions']), DB::table('questions')->count());
        $this->assertSame(count($data['answers']), DB::table('answers')->count());

        foreach ($data['questions'] as $sourceQuestion) {
            $quiz = $quizzes->get((int) $sourceQuestion['quiz_id']);
            $this->assertNotNull($quiz, "Question {$sourceQuestion['id']} must reference an imported quiz.");

            $question = $quiz->questions->firstWhere('id', (int) $sourceQuestion['id']);
            $this->assertNotNull($question, "Question {$sourceQuestion['id']} must be imported under its quiz.");
            $this->assertNotEmpty($question->answers, "Question {$sourceQuestion['id']} must have answers.");
        }

        foreach ($data['answers'] as $sourceAnswer) {
            $question = DB::table('questions')->find($sourceAnswer['question_id']);
            $this->assertNotNull($question, "Answer {$sourceAnswer['id']} must reference an imported question.");
        }
    }
}