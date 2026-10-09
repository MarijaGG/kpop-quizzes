<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Services\QuizResultDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $groupFilter = $request->query('group');
        $query = Quiz::query()->where('is_published', true)->with('questions.answers')->orderBy('id');

        if ($groupFilter === 'none') {
            $query->whereNull('group_id')->whereNull('member_id');
        } elseif ($groupFilter !== null) {
            $query->where('group_id', $groupFilter);
        }

        $quizzes = $query->get()
            ->filter(fn (Quiz $quiz) => $quiz->publicationErrors() === [])
            ->values();

        return view('quizzes.index', [
            'quizzes' => $quizzes,
            'groups' => Group::orderBy('name')->get(),
            'groupFilter' => $groupFilter,
        ]);
    }

    public function show($id, QuizResultDataService $resultData)
    {
        $quiz = Quiz::where('is_published', true)->findOrFail($id);
        abort_if($quiz->load('questions.answers')->publicationErrors(), 404);

        $quizStats = $quiz->settings['quiz_stats'] ?? [];
        $entities = $resultData->forRun([
            'questions' => $quiz->questions->map(fn ($question) => [
                'answers' => $question->answers->map(fn ($answer) => [
                    'target_type' => data_get($answer->meta, 'target_type'),
                    'target_id' => data_get($answer->meta, 'target_id'),
                ])->all(),
            ])->all(),
        ], $quizStats);

        return view('quizzes.show', [
            'quiz' => $quiz,
            'questions_count' => $quiz->questions()->count(),
            'quizStats' => $quizStats,
            'members' => $entities['members']->map->toArray()->all(),
            'groups' => $entities['groups']->map->toArray()->all(),
            'albums' => $entities['albums']->map->toArray()->all(),
        ]);
    }

    public function start($id)
    {
        $quiz = Quiz::with('questions.answers')->where('is_published', true)->findOrFail($id);
        if ($errors = $quiz->publicationErrors()) {
            return redirect()->route('quizzes.index')->with('error', implode(' ', $errors));
        }
        $questions = $quiz->questions->shuffle();

        if ($questions->isEmpty()) {
            return redirect()->route('quizzes.show', $id)->with('error', 'No questions');
        }

        $run = ['quiz_id' => (int) $id, 'attempt_key' => (string) Str::uuid(), 'questions' => []];
        foreach ($questions as $question) {
            $run['questions'][] = [
                'id' => $question->id,
                'text' => $question->text,
                'order' => $question->order,
                'answers' => $question->answers->shuffle()->map(function ($answer) {
                    $meta = $answer->meta ?? [];

                    return [
                        'id' => $answer->id,
                        'text' => $answer->text,
                        'target_type' => $meta['target_type'] ?? null,
                        'target_id' => $meta['target_id'] ?? null,
                    ];
                })->values()->all(),
            ];
        }

        session(["quiz_run.$id" => ['quiz' => $run, 'index' => 0, 'responses' => []]]);

        return redirect()->route('quizzes.take', $id);
    }

    public function take($id)
    {
        $state = session("quiz_run.$id");
        if (empty($state)) {
            return redirect()->route('quizzes.start', $id);
        }

        $run = $state['quiz'];
        $index = $state['index'] ?? 0;
        Quiz::findOrFail($id);
        if (! isset($run['questions'][$index])) {
            return redirect()->route('quizzes.result', $id);
        }

        return view('quizzes.take', [
            'quiz_id' => $id,
            'question' => (object) $run['questions'][$index],
            'index' => $index,
            'total' => count($run['questions']),
        ]);
    }

    public function answer(Request $request, $id)
    {
        $state = session("quiz_run.$id");
        if (empty($state)) {
            return redirect()->route('quizzes.start', $id);
        }

        $run = $state['quiz'];
        $index = $state['index'] ?? 0;
        $question = $run['questions'][$index] ?? null;
        $choice = $request->validate(['choice' => 'required|integer']);
        $validChoice = collect($question['answers'] ?? [])->contains('id', (int) $choice['choice']);
        if (! $validChoice) {
            return back()->withErrors(['choice' => 'Choose a valid answer.']);
        }

        $responses = $state['responses'] ?? [];
        $responses[] = (int) $choice['choice'];
        $index++;
        session(["quiz_run.$id" => ['quiz' => $run, 'index' => $index, 'responses' => $responses]]);

        if ($index >= count($run['questions'])) {
            return redirect()->route('quizzes.result', $id);
        }

        return redirect()->route('quizzes.take', $id);
    }

    public function result($id, QuizResultDataService $resultData)
    {
        $state = session("quiz_run.$id");
        if (empty($state)) {
            return redirect()->route('quizzes.start', $id);
        }

        $run = $state['quiz'] ?? [];
        $questions = $run['questions'] ?? [];
        $responses = $state['responses'] ?? [];
        $total = count($questions);

        if ((int) ($run['quiz_id'] ?? 0) !== (int) $id || $total === 0) {
            session()->forget("quiz_run.$id");

            return redirect()->route('quizzes.start', $id);
        }

        if (($state['index'] ?? 0) < $total) {
            return redirect()->route('quizzes.take', $id);
        }

        if (($state['index'] ?? 0) !== $total || count($responses) !== $total) {
            session()->forget("quiz_run.$id");

            return redirect()->route('quizzes.start', $id);
        }

        $quiz = Quiz::findOrFail($id);
        if (! $this->hasValidCompletedResponses($run, $responses)) {
            session()->forget("quiz_run.$id");

            return redirect()->route('quizzes.start', $id);
        }

        $entityData = $resultData->forRun($run, $quiz->settings['quiz_stats'] ?? []);
        $members = $entityData['members'];
        $groups = $entityData['groups'];
        $albums = $entityData['albums'];
        $memberById = $members->keyBy('id');
        $groupById = $groups->keyBy('id');
        $albumById = $albums->keyBy('id');
        $answersById = collect($run['questions'])->flatMap(fn ($question) => $question['answers'])->keyBy('id');

        $knowledgeQuiz = $quiz->member_id !== null;
        if ($knowledgeQuiz) {
            $total = count($run['questions']);
            $correct = collect($responses)->filter(function ($answerId) use ($answersById, $quiz) {
                $answer = $answersById->get($answerId);

                return ($answer['target_type'] ?? null) === 'member'
                    && (int) ($answer['target_id'] ?? 0) === (int) $quiz->member_id;
            })->count();
            $result = (object) [
                'percent' => $total ? round($correct / $total * 100) : 0,
                'correct' => $correct,
                'total' => $total,
                'member_id' => $quiz->member_id,
                'name' => $memberById->get($quiz->member_id)?->name ?? 'Knowledge result',
            ];
            $quizStats = $this->finalizeQuizResult($quiz, $state, 'percent', $result, $total);
            $newTitleAward = auth()->user()?->awardTitleForQuizResult($quiz, 'percent', $result, $correct, $total);

            return view('quizzes.result', [
                'quiz_id' => $id,
                'resultType' => 'percent',
                'result' => $result,
                'members' => $members->map->toArray()->all(),
                'quizStats' => $quizStats,
                'newTitle' => $this->newTitleData($newTitleAward),
            ]);
        }

        $scores = ['member' => [], 'group' => [], 'album' => []];
        foreach ($responses as $answerId) {
            $answer = $answersById->get($answerId);
            $type = $answer['target_type'] ?? null;
            $targetId = $answer['target_id'] ?? null;
            if (! $targetId || ! isset($scores[$type])) {
                continue;
            }
            $scores[$type][$targetId] = ($scores[$type][$targetId] ?? 0) + 1;
            if ($type === 'group') {
                $this->spreadMemberScore($scores['member'], $members, $targetId);
            } elseif ($type === 'album') {
                $groupId = $albumById->get($targetId)?->group_id;
                if ($groupId) {
                    $scores['group'][$groupId] = ($scores['group'][$groupId] ?? 0) + 1;
                    $this->spreadMemberScore($scores['member'], $members, $groupId);
                }
            }
        }

        foreach ($scores as &$scoreSet) {
            arsort($scoreSet);
        }
        unset($scoreSet);

        $typeCounts = collect($run['questions'])->flatMap(fn ($question) => $question['answers'])
            ->pluck('target_type')->countBy()->sortDesc();
        $preferredType = $typeCounts->keys()->first();
        $resultType = $preferredType && ! empty($scores[$preferredType]) ? $preferredType : 'member';
        $resultId = array_key_first($scores[$resultType]);
        $lookup = ['member' => $memberById, 'group' => $groupById, 'album' => $albumById];
        $result = $resultId !== null ? $lookup[$resultType]->get($resultId) : null;

        if (! $result) {
            return view('quizzes.result', ['quiz_id' => $id, 'resultType' => null, 'result' => null, 'members' => $members->map->toArray()->all()]);
        }

        $quizStats = $this->finalizeQuizResult($quiz, $state, $resultType, $result, count($run['questions']));
        $candidates = $this->candidates($run, $resultType, $lookup);
        $newTitleAward = auth()->user()?->awardTitleForQuizResult($quiz, $resultType, $result);

        return view('quizzes.result', [
            'quiz_id' => $id,
            'resultType' => $resultType,
            'result' => $result,
            'members' => $members->map->toArray()->all(),
            'quizStats' => $quizStats,
            'candidates' => $candidates,
            'newTitle' => $this->newTitleData($newTitleAward),
        ]);
    }

    private function newTitleData(?\App\Models\UserTitle $award): ?array
    {
        return $award ? ['key' => $award->title_key, 'label' => $award->title_label] : null;
    }

    private function hasValidCompletedResponses(array $run, array $responses): bool
    {
        $runQuestions = $run['questions'] ?? [];

        if (count($runQuestions) === 0 || count($responses) !== count($runQuestions)) {
            return false;
        }

        foreach (array_values($runQuestions) as $index => $runQuestion) {
            if (! is_array($runQuestion) || ! isset($runQuestion['id'], $runQuestion['answers'], $responses[$index])) {
                return false;
            }

            $answerId = (int) $responses[$index];
            $snapshotHasAnswer = collect($runQuestion['answers'])->contains(fn ($answer) => (int) ($answer['id'] ?? 0) === $answerId);

            if (! $snapshotHasAnswer) {
                return false;
            }
        }

        return true;
    }

    private function spreadMemberScore(array &$scores, $members, $groupId): void
    {
        $groupMembers = $members->where('group_id', $groupId);
        $share = 1 / max($groupMembers->count(), 1);
        foreach ($groupMembers as $member) {
            $scores[$member->id] = ($scores[$member->id] ?? 0) + $share;
        }
    }

    private function finalizeQuizResult(Quiz $quiz, array $state, string $resultType, object $result, int $total): array
    {
        if (! auth()->check()) {
            return $quiz->settings['quiz_stats'] ?? [];
        }

        $run = $state['quiz'];
        $correctAnswers = $resultType === 'percent' ? (int) ($result->correct ?? 0) : null;
        $attemptKey = $run['attempt_key'] ?? null;
        if (! $attemptKey) {
            return $quiz->settings['quiz_stats'] ?? [];
        }

        $knowledge = $resultType === 'percent';

        return DB::transaction(function () use ($quiz, $attemptKey, $resultType, $result, $total, $knowledge, $correctAnswers): array {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->id);
            $inserted = QuizResult::query()->insertOrIgnore([
                'user_id' => auth()->id(),
                'quiz_id' => $quiz->id,
                'attempt_key' => $attemptKey,
                'quiz_name' => $quiz->name,
                'result_type' => $knowledge ? 'knowledge' : 'personality',
                'correct_answers' => $knowledge ? $correctAnswers : null,
                'total_questions' => $knowledge ? $total : null,
                'result_name' => $knowledge ? null : ($result->name ?? $result->title ?? 'Quiz result'),
                'total_points' => $knowledge ? (int) ($result->percent ?? 0) : 0,
                'details' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $stats = $lockedQuiz->settings['quiz_stats'] ?? [];
            if ($inserted > 0) {
                if ($resultType === 'percent') {
                    $percent = (int) ($result->percent ?? 0);
                    $bucket = $percent <= 30 ? '0-30' : ($percent <= 60 ? '31-60' : ($percent <= 90 ? '61-90' : '91-100'));
                    $stats['percent_buckets'][$bucket] = ($stats['percent_buckets'][$bucket] ?? 0) + 1;
                } else {
                    $stats[$resultType] ??= [];
                    $key = (string) ($result->id ?? '');
                    if ($key !== '') {
                        $stats[$resultType][$key] = ($stats[$resultType][$key] ?? 0) + 1;
                    }
                }

                $settings = $lockedQuiz->settings ?? [];
                $settings['quiz_stats'] = $stats;
                $lockedQuiz->update(['settings' => $settings]);
            }

            $quiz->setAttribute('settings', $lockedQuiz->settings);

            return $lockedQuiz->settings['quiz_stats'] ?? [];
        });
    }

    private function candidates(array $run, string $type, array $lookup): array
    {
        $seen = [];
        $result = [];
        foreach ($run['questions'] as $question) {
            foreach ($question['answers'] as $answer) {
                if (($answer['target_type'] ?? null) !== $type || empty($answer['target_id'])) {
                    continue;
                }
                $key = (string) $answer['target_id'];
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                if ($entity = $lookup[$type]->get($answer['target_id'])) {
                    $result[] = $entity;
                }
            }
        }

        return $result;
    }
}
