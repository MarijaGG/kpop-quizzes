<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GuessIdolImage;
use App\Models\Member;
use App\Models\QuizResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GuessIdolController extends Controller
{
    public function index(): View
    {
        return view('guess-idol.index', [
            'groups' => Group::orderBy('name')->get()->map->toArray()->all(),
            'available' => GuessIdolImage::query()->select('group_id', 'difficulty')->distinct()->get(),
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        session()->forget('guess_idol_run');
        $validated = $request->validate([
            'group_id' => ['required', 'integer', 'exists:groups,id'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
        ]);

        $images = GuessIdolImage::where('group_id', $validated['group_id'])
            ->where('difficulty', $validated['difficulty'])
            ->inRandomOrder()
            ->get();
        if ($images->count() < 5) {
            return back()->withErrors(['difficulty' => 'This difficulty needs at least 5 images before it can be played.']);
        }

        $members = Member::where('group_id', $validated['group_id'])->get();
        if ($members->count() < 4) {
            return back()->withErrors(['group_id' => 'This group needs at least four members.']);
        }

        $questions = $images->take(5)->map(function (GuessIdolImage $image) use ($members) {
            $otherIds = $members->pluck('id')->reject(fn ($id) => (int) $id === (int) $image->member_id)->shuffle()->take(3)->values()->all();
            $options = collect(array_merge([(int) $image->member_id], array_map('intval', $otherIds)))->shuffle()->values()->all();

            return [
                'image_id' => $image->id,
                'image' => $image->image,
                'correct_member_id' => (int) $image->member_id,
                'options' => $options,
            ];
        })->values()->all();

        session([
            'guess_idol_run' => [
                'attempt_key' => (string) Str::uuid(),
                'group_id' => (int) $validated['group_id'],
                'difficulty' => $validated['difficulty'],
                'questions' => $questions,
                'index' => 0,
                'responses' => [],
            ],
        ]);

        return redirect()->route('guess-idol.take');
    }

    public function take(): View|RedirectResponse
    {
        $run = session('guess_idol_run');
        if (empty($run) || ! isset($run['questions'][$run['index'] ?? 0])) {
            return redirect()->route('guess-idol.index');
        }

        $question = $run['questions'][$run['index']];
        $members = Member::whereIn('id', $question['options'])->get()->keyBy('id');
        $options = collect($question['options'])->map(fn ($id) => $members[(int) $id] ?? null)->filter()->values();

        return view('guess-idol.take', [
            'question' => $question,
            'options' => $options,
            'index' => $run['index'],
            'total' => count($run['questions']),
        ]);
    }

    public function answer(Request $request): RedirectResponse
    {
        $run = session('guess_idol_run');
        if (empty($run)) {
            return redirect()->route('guess-idol.index');
        }

        $index = $run['index'] ?? 0;
        $question = $run['questions'][$index] ?? null;
        $validated = $request->validate(['choice' => ['required', 'integer', 'in:'.implode(',', $question['options'] ?? [])]]);
        $run['responses'][] = [
            'choice' => (int) $validated['choice'],
            'correct' => (int) $question['correct_member_id'],
        ];
        $run['index'] = $index + 1;

        if ($run['index'] >= count($run['questions'])) {
            $correct = collect($run['responses'])->filter(fn ($response) => $response['choice'] === $response['correct'])->count();
            $run['score'] = $correct;
            $run['newTitle'] = $this->saveResult($run);
            session(['guess_idol_run' => $run]);

            return redirect()->route('guess-idol.result');
        }

        session(['guess_idol_run' => $run]);

        return redirect()->route('guess-idol.take');
    }

    public function result(): View|RedirectResponse
    {
        $run = session('guess_idol_run');
        if (empty($run) || ! isset($run['score'])) {
            return redirect()->route('guess-idol.index');
        }

        return view('guess-idol.result', [
            'run' => $run,
            'group' => Group::find($run['group_id'])?->toArray(),
            'newTitle' => $run['newTitle'] ?? null,
        ]);
    }

    private function saveResult(array $run): ?array
    {
        if (! auth()->check() || empty($run['attempt_key'])) {
            return null;
        }
        $group = Group::find($run['group_id']);
        $groupName = $group?->name ?? 'TXT';
        $difficulty = ucfirst($run['difficulty']);
        $total = count($run['questions']);
        $inserted = QuizResult::query()->insertOrIgnore([
            'user_id' => auth()->id(),
            'quiz_id' => null,
            'attempt_key' => $run['attempt_key'],
            'quiz_name' => "Guess the {$groupName} Member — {$difficulty}",
            'result_type' => 'guess_idol',
            'correct_answers' => $run['score'],
            'total_questions' => $total,
            'result_name' => null,
            'total_points' => $run['score'],
            'details' => json_encode(['difficulty' => $run['difficulty'], 'group_id' => $run['group_id']]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted === 0) {
            return null;
        }

        if ($group && $run['difficulty'] === 'hard' && $run['score'] === $total) {
            $award = auth()->user()->awardTitle('guess-idol-group-'.$group->id.'-guru', "{$group->name} Guru");

            return $award ? ['key' => $award->title_key, 'label' => $award->title_label] : null;
        }

        return null;
    }
}
