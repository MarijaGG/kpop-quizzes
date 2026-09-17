<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Group;
use App\Models\Member;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $groupFilter = $request->query('group');
        $query = Quiz::query()->orderBy('id');

        if ($groupFilter === 'none') {
            $query->whereNull('group_id')->whereNull('member_id');
        } elseif ($groupFilter !== null) {
            $query->where('group_id', $groupFilter);
        }

        return view('quizzes.index', [
            'quizzes' => $query->get(),
            'groups' => Group::orderBy('name')->get(),
            'groupFilter' => $groupFilter,
        ]);
    }

    public function show($id)
    {
        $quiz = Quiz::findOrFail($id);

        return view('quizzes.show', [
            'quiz' => $quiz,
            'questions_count' => $quiz->questions()->count(),
            'quizStats' => $quiz->settings['quiz_stats'] ?? [],
            'members' => Member::orderBy('name')->get(),
            'groups' => Group::orderBy('name')->get(),
            'albums' => Album::orderBy('title')->get(),
        ]);
    }

    public function start($id)
    {
        session()->forget("quiz_result_saved.$id");
        $quiz = Quiz::with('questions.answers')->findOrFail($id);
        $questions = $quiz->questions->shuffle();

        if ($questions->isEmpty()) {
            return redirect()->route('quizzes.show', $id)->with('error', 'No questions');
        }

        $run = ['quiz_id' => (int) $id, 'questions' => []];
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
        if (Quiz::findOrFail($id)->questions()->count() !== count($run['questions'])) {
            session()->forget("quiz_run.$id");
            return redirect()->route('quizzes.start', $id);
        }
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
            session(["quiz_ready_to_finalize.$id" => true]);
            return redirect()->route('quizzes.result', $id);
        }

        return redirect()->route('quizzes.take', $id);
    }

    public function result($id)
    {
        $state = session("quiz_run.$id");
        if (empty($state)) {
            return redirect()->route('quizzes.start', $id);
        }

        $quiz = Quiz::findOrFail($id);
        $run = $state['quiz'];
        $responses = $state['responses'] ?? [];
        $members = Member::orderBy('name')->get();
        $groups = Group::orderBy('name')->get();
        $albums = Album::orderBy('title')->get();
        $memberById = $members->keyBy('id');
        $groupById = $groups->keyBy('id');
        $albumById = $albums->keyBy('id');
        $answersById = collect($run['questions'])->flatMap(fn ($question) => $question['answers'])->keyBy('id');

        $knowledgeQuiz = $quiz->member_id && stripos($quiz->name, 'how well do you know') !== false;
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
            $this->saveQuizHistory($quiz, 'percent', $result, $total);
            $this->updateStats($quiz, 'percent', $result);
            $newTitleAward = auth()->user()?->awardTitleForPerfectQuiz($quiz, $correct, $total);

            return view('quizzes.result', [
                'quiz_id' => $id,
                'resultType' => 'percent',
                'result' => $result,
                'members' => $members->map->toArray()->all(),
                'newTitle' => $newTitleAward ? User::TITLES[$newTitleAward->title_key] + ['key' => $newTitleAward->title_key] : null,
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

        $this->saveQuizHistory($quiz, $resultType, $result, count($run['questions']));
        $quizStats = $this->updateStats($quiz, $resultType, $result);
        $candidates = $this->candidates($run, $resultType, $lookup);

        return view('quizzes.result', [
            'quiz_id' => $id,
            'resultType' => $resultType,
            'result' => $result,
            'members' => $members->map->toArray()->all(),
            'quizStats' => $quizStats,
            'candidates' => $candidates,
        ]);
    }

    private function spreadMemberScore(array &$scores, $members, $groupId): void
    {
        $groupMembers = $members->where('group_id', $groupId);
        $share = 1 / max($groupMembers->count(), 1);
        foreach ($groupMembers as $member) {
            $scores[$member->id] = ($scores[$member->id] ?? 0) + $share;
        }
    }

    private function saveQuizHistory(Quiz $quiz, string $resultType, object $result, int $total): void
    {
        $sessionKey = "quiz_result_saved.{$quiz->id}";
        if (session()->has($sessionKey) || ! session()->pull("quiz_ready_to_finalize.{$quiz->id}") || ! auth()->check()) {
            return;
        }

        $knowledge = $resultType === 'percent';
        QuizResult::create([
            'user_id' => auth()->id(),
            'quiz_id' => $quiz->id,
            'quiz_name' => $quiz->name,
            'result_type' => $knowledge ? 'knowledge' : 'personality',
            'correct_answers' => $knowledge ? (int) ($result->correct ?? 0) : null,
            'total_questions' => $knowledge ? $total : null,
            'result_name' => $knowledge ? null : ($result->name ?? $result->title ?? 'Quiz result'),
            'total_points' => $knowledge ? (int) ($result->percent ?? 0) : 0,
            'details' => null,
        ]);
        session([$sessionKey => true]);
    }

    private function updateStats(Quiz $quiz, string $resultType, object $result): array
    {
        $stats = $quiz->settings['quiz_stats'] ?? [];
        if (! session()->has("quiz_result_saved.{$quiz->id}")) {
            return $stats;
        }

        if ($resultType === 'percent') {
            $percent = (int) ($result->percent ?? 0);
            $bucket = $percent <= 30 ? '0-30' : ($percent <= 60 ? '31-60' : ($percent <= 90 ? '61-90' : '91-100'));
            $stats['percent_buckets'][$bucket] = ($stats['percent_buckets'][$bucket] ?? 0) + 1;
        } else {
            $key = (string) ($result->id ?? '');
            if ($key !== '') {
                $stats[$resultType][$key] = ($stats[$resultType][$key] ?? 0) + 1;
            }
        }

        $settings = $quiz->settings ?? [];
        $settings['quiz_stats'] = $stats;
        $quiz->update(['settings' => $settings]);
        return $stats;
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
