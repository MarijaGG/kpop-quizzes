<?php

namespace App\Http\Controllers\Admin;

use App\Models\Group;
use App\Models\Member;
use App\Models\Quiz;
use App\Services\MediaDeletionService;
use App\Support\UploadedFileReplacement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizController extends BaseAdminController
{
    public function index(Request $request)
    {
        $query = Quiz::with(['questions.answers', 'group', 'member'])->latest();
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        return view('admin.quizzes.index', ['quizzes' => $query->paginate(20)->withQueryString()]);
    }

    public function create()
    {
        return view('admin.quizzes.create', [
            'groups' => Group::orderBy('name')->get(),
            'members' => Member::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, 'array|size:10');

        $createQuiz = function (?string $imagePath = null) use ($data): Quiz {
            return DB::transaction(function () use ($data, $imagePath): Quiz {
                $quiz = Quiz::create($this->payload($data, null, $imagePath) + ['is_published' => false]);
                $order = 1;

                foreach ($data['questions'] as $text) {
                    $text = trim($text ?? '');
                    if ($text === '') {
                        continue;
                    }

                    $quiz->questions()->create(['text' => $text, 'order' => $order++]);
                }

                return $quiz;
            });
        };
        $quiz = $request->hasFile('image')
            ? UploadedFileReplacement::persist($request->file('image'), 'images/quizzes', 'public', null, $createQuiz(...))
            : $createQuiz();

        return redirect()->route('admin.quizzes.edit', $quiz->id)
            ->with('success', 'Draft created. Add answers to each question, then publish when it is ready.');
    }

    public function edit($quiz)
    {
        $quiz = Quiz::with('questions')->findOrFail(is_object($quiz) ? $quiz->id : $quiz);

        return view('admin.quizzes.edit', [
            'quiz' => $quiz,
            'groups' => Group::orderBy('name')->get(),
            'members' => Member::orderBy('name')->get(),
            'questions' => $quiz->questions->sortBy('order')->values(),
        ]);
    }

    public function update(Request $request, $quiz)
    {
        $quiz = Quiz::findOrFail(is_object($quiz) ? $quiz->id : $quiz);
        $data = $this->validated($request, 'sometimes|array');

        $persistChanges = function (?string $imagePath = null) use ($quiz, $data): void {
            DB::transaction(function () use ($quiz, $data, $imagePath): void {
                $quiz = Quiz::query()->whereKey($quiz->id)->lockForUpdate()->firstOrFail();
                $quiz->update($this->payload($data, $quiz, $imagePath));

                if (array_key_exists('questions', $data)) {
                    foreach ($data['questions'] as $order => $text) {
                        $text = trim($text ?? '');
                        if ($text === '') {
                            continue;
                        }

                        $questionId = $data['question_ids'][$order] ?? null;
                        if ($questionId) {
                            $question = $quiz->questions()->findOrFail($questionId);
                            $question->update(['text' => $text, 'order' => $order + 1]);
                        } else {
                            $quiz->questions()->create(['text' => $text, 'order' => $order + 1]);
                        }
                    }
                }

                $isPublished = (bool) ($data['is_published'] ?? $quiz->is_published);
                if ($isPublished && array_key_exists('is_published', $data)) {
                    $errors = $quiz->fresh()->load('questions.answers')->publicationErrors();
                    if ($errors) {
                        throw ValidationException::withMessages(['is_published' => $errors]);
                    }
                }

                if (array_key_exists('is_published', $data)) {
                    $quiz->update(['is_published' => $isPublished]);
                }
            });
        };
        if ($request->hasFile('image')) {
            UploadedFileReplacement::persist($request->file('image'), 'images/quizzes', 'public', $quiz->image, $persistChanges(...));
        } else {
            $persistChanges();
        }

        return redirect()->route('admin.quizzes.index')->with('success', 'Quiz updated');
    }

    public function destroy(MediaDeletionService $mediaDeletion, $quiz)
    {
        $quiz = Quiz::findOrFail(is_object($quiz) ? $quiz->id : $quiz);
        $mediaDeletion->delete($quiz);

        return redirect()->route('admin.quizzes.index')->with('success', 'Quiz deleted');
    }

    private function validated(Request $request, string $questions): array
    {
        $data = $request->validate([
            'group_id' => 'nullable|exists:groups,id',
            'member_id' => 'nullable|exists:members,id',
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048',
            'questions' => $questions,
            'questions.*' => 'nullable|string|max:5000',
            'question_ids' => 'sometimes|array',
            'question_ids.*' => 'nullable|integer',
            'is_published' => 'sometimes|boolean',
        ]);

        $groupId = $data['group_id'] ?? null;
        $memberId = $data['member_id'] ?? null;
        if ($memberId && (! $groupId || ! Member::whereKey($memberId)->where('group_id', $groupId)->exists())) {
            throw ValidationException::withMessages([
                'member_id' => 'The selected member must belong to the selected group.',
            ]);
        }

        return $data;
    }

    private function payload(array $data, ?Quiz $quiz = null, ?string $imagePath = null): array
    {
        $payload = [
            'group_id' => $data['group_id'] ?? null,
            'member_id' => $data['member_id'] ?? null,
            'name' => $data['name'],
        ];

        if ($imagePath) {
            $payload['image'] = $imagePath;
        }

        return $payload;
    }
}
