<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Answer;
use App\Models\Group;
use App\Models\Member;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApiDataSeeder extends Seeder
{
    public function run(): void
    {
        $path = resource_path('data/api.json');

        if (! is_file($path)) {
            throw new RuntimeException("API data file was not found: {$path}");
        }

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data): void {
            $this->seedGroups($data['groups'] ?? []);
            $this->seedMembers($data['members'] ?? []);
            $this->seedAlbums($data['albums'] ?? []);
            $this->seedQuizzes($data['quizzes'] ?? [], $data['quiz_stats'] ?? []);
            $this->seedQuestions($data['questions'] ?? []);
            $this->seedAnswers($data['answers'] ?? []);
        });
    }

    private function seedGroups(array $groups): void
    {
        foreach ($groups as $group) {
            Group::updateOrCreate(
                ['id' => $group['id']],
                [
                    'name' => $group['name'],
                    'debut_date' => $group['debut_date'] ?? null,
                    'concept' => $group['concept'] ?? null,
                    'about' => $group['about'] ?? null,
                    'description' => $group['description'] ?? null,
                    'image' => $group['image'] ?? null,
                ]
            );
        }
    }

    private function seedMembers(array $members): void
    {
        foreach ($members as $member) {
            Member::updateOrCreate(
                ['id' => $member['id']],
                [
                    'group_id' => $member['group_id'] ?? null,
                    'name' => $member['name'],
                    'about' => $member['about'] ?? null,
                    'traits' => $member['traits'] ?? null,
                    'description' => $member['description'] ?? null,
                    'image' => $member['image'] ?? null,
                ]
            );
        }
    }

    private function seedAlbums(array $albums): void
    {
        foreach ($albums as $album) {
            $values = [
                'group_id' => $album['group_id'] ?? null,
                'title' => $album['title'],
                'release_date' => $album['release_date'] ?? null,
                'concept' => $album['concept'] ?? null,
                'vibe' => $album['vibe'] ?? null,
                'concept_traits' => $album['concept_traits'] ?? null,
                'description' => $album['description'] ?? null,
                'image' => $album['image'] ?? null,
            ];

            if (isset($album['id'])) {
                Album::updateOrCreate(['id' => $album['id']], $values);
            } else {
                Album::updateOrCreate(
                    ['group_id' => $values['group_id'], 'title' => $values['title']],
                    $values
                );
            }
        }
    }

    private function seedQuizzes(array $quizzes, array $quizStats): void
    {
        foreach ($quizzes as $quiz) {
            Quiz::updateOrCreate(
                ['id' => $quiz['id']],
                [
                    'group_id' => $quiz['group_id'] ?? null,
                    'member_id' => $quiz['member_id'] ?? null,
                    'name' => $quiz['name'],
                    'image' => $quiz['image'] ?? null,
                    'settings' => isset($quizStats[(string) $quiz['id']])
                        ? ['quiz_stats' => $quizStats[(string) $quiz['id']]]
                        : null,
                ]
            );
        }
    }

    private function seedQuestions(array $questions): void
    {
        foreach ($questions as $question) {
            if (! Quiz::whereKey($question['quiz_id'])->exists()) {
                $this->command?->warn("Skipping question {$question['id']}: quiz {$question['quiz_id']} does not exist.");
                continue;
            }

            Question::updateOrCreate(
                ['id' => $question['id']],
                [
                    'quiz_id' => $question['quiz_id'],
                    'text' => $question['text'],
                    'order' => $question['order'] ?? 0,
                    'meta' => $question['meta'] ?? null,
                ]
            );
        }
    }

    private function seedAnswers(array $answers): void
    {
        // The legacy source identifies quiz 1 answers as questions 1-10, while its database questions are 21-30.
        $questionIdMap = array_combine(range(1, 10), range(21, 30));

        foreach ($answers as $answer) {
            $questionId = $questionIdMap[$answer['question_id']] ?? $answer['question_id'];

            if (! Question::whereKey($questionId)->exists()) {
                $this->command?->warn("Skipping answer {$answer['id']}: question {$answer['question_id']} does not exist.");
                continue;
            }

            $meta = $answer['meta'] ?? [];

            foreach (['target_type', 'target_id'] as $key) {
                if (array_key_exists($key, $answer)) {
                    $meta[$key] = $answer[$key];
                }
            }

            Answer::updateOrCreate(
                ['id' => $answer['id']],
                [
                    'question_id' => $questionId,
                    'text' => $answer['text'],
                    'points' => $answer['points'] ?? 0,
                    'meta' => $meta ?: null,
                ]
            );
        }
    }
}