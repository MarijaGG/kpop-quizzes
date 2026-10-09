<?php

namespace App\Http\Controllers\Admin;

use App\Models\Album;
use App\Models\Group;
use App\Models\Member;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuestionController extends BaseAdminController
{
    public function index($quizId)
    {
        $quiz = Quiz::findOrFail($quizId);

        return view('admin.questions.index', [
            'questions' => $quiz->questions()->orderBy('order')->paginate(50),
            'quiz' => $quiz,
        ]);
    }

    public function create($quizId)
    {
        return view('admin.questions.create', ['quiz' => Quiz::findOrFail($quizId)]);
    }

    public function store(Request $request, $quizId)
    {
        $data = $request->validate(['text' => 'required|string|max:5000']);
        $question = DB::transaction(function () use ($quizId, $data): Question {
            $quiz = Quiz::query()->whereKey($quizId)->lockForUpdate()->firstOrFail();
            $question = $quiz->questions()->create([
                'text' => trim($data['text']),
                'order' => ($quiz->questions()->max('order') ?? 0) + 1,
            ]);

            if ($quiz->is_published) {
                $quiz->update(['is_published' => false]);
            }

            return $question;
        });

        return redirect()->route('admin.quizzes.questions.edit', [$quizId, $question->id])
            ->with('success', 'Question created. Add its answers below.');
    }

    public function edit($quizId, $questionId)
    {
        $quiz = Quiz::findOrFail($quizId);
        $question = $quiz->questions()->findOrFail($questionId);
        $answers = $question->answers()->get()->map(fn ($answer) => (object) array_merge(
            $answer->toArray(),
            $answer->meta ?? [],
        ));

        return view('admin.questions.edit', [
            'quiz_id' => $quizId,
            'question' => $question,
            'answers' => $answers,
            'members' => Member::when($quiz->group_id, fn ($query) => $query->where('group_id', $quiz->group_id))
                ->orderBy('name')->get(),
            'groups' => Group::orderBy('name')->get(),
            'albums' => Album::when($quiz->group_id, fn ($query) => $query->where('group_id', $quiz->group_id))
                ->orderBy('title')->get(),
            'question_target_type' => $answers->firstWhere('target_type', '!=', null)?->target_type ?? 'member',
        ]);
    }

    public function update(Request $request, $quizId, $questionId)
    {
        $quiz = Quiz::findOrFail($quizId);
        $question = $quiz->questions()->findOrFail($questionId);
        $targetType = $request->input('target_type');
        $targetTable = match ($targetType) {
            'group' => 'groups',
            'member' => 'members',
            'album' => 'albums',
            default => null,
        };
        $targetIdRules = ['nullable', 'integer', 'min:1'];

        if ($targetTable) {
            $existsRule = Rule::exists($targetTable, 'id');
            if ($quiz->group_id && in_array($targetType, ['member', 'album'], true)) {
                $existsRule->where('group_id', $quiz->group_id);
            }
            $targetIdRules[] = $existsRule;
        }

        $data = $request->validate([
            'answers' => ['array', 'max:8'],
            'answers.*.id' => [
                'nullable', 'integer', 'distinct',
                Rule::exists('answers', 'id')->where('question_id', $question->id),
            ],
            'answers.*.text' => ['nullable', 'string', 'max:1000'],
            'target_type' => ['nullable', 'in:group,member,album'],
            'answers.*.target_id' => $targetIdRules,
        ]);

        foreach ($data['answers'] ?? [] as $index => $answer) {
            if (! empty($answer['text']) && ! empty($answer['target_id']) && ! $targetTable) {
                throw ValidationException::withMessages([
                    "answers.{$index}.target_id" => 'Choose a target type for this answer.',
                ]);
            }
        }

        DB::transaction(function () use ($question, $data): void {
            $lockedQuestion = Question::query()->whereKey($question->id)->lockForUpdate()->firstOrFail();
            $existingAnswers = $lockedQuestion->answers()->lockForUpdate()->get()->keyBy('id');

            foreach ($data['answers'] ?? [] as $order => $answer) {
                $answerId = isset($answer['id']) ? (int) $answer['id'] : null;
                $text = trim($answer['text'] ?? '');

                if ($answerId && ! $existingAnswers->has($answerId)) {
                    throw ValidationException::withMessages([
                        'answers' => 'The answer list changed while it was being edited. Reload the page and try again.',
                    ]);
                }

                if ($text === '') {
                    if ($answerId) {
                        $existingAnswers->get($answerId)->delete();
                    }

                    continue;
                }

                $payload = [
                    'text' => $text,
                    'meta' => [
                        'order' => $order + 1,
                        'target_type' => $data['target_type'] ?? null,
                        'target_id' => ($answer['target_id'] ?? '') !== '' ? (int) $answer['target_id'] : null,
                    ],
                ];

                if ($answerId) {
                    $existingAnswers->get($answerId)->update($payload);
                } else {
                    $lockedQuestion->answers()->create($payload + ['points' => 0]);
                }
            }

            $quiz = $lockedQuestion->quiz()->firstOrFail();
            if ($quiz->is_published) {
                $errors = $quiz->load('questions.answers')->publicationErrors();
                if ($errors) {
                    throw ValidationException::withMessages(['answers' => $errors]);
                }
            }
        });

        return redirect()->route('admin.quizzes.questions.index', $quizId)->with('success', 'Answers saved');
    }
}
