<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Answer;
use App\Models\Group;
use App\Models\Member;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\MediaDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaticApiController extends Controller
{
    private const MODELS = [
        'groups' => Group::class,
        'members' => Member::class,
        'albums' => Album::class,
        'quizzes' => Quiz::class,
        'questions' => Question::class,
        'answers' => Answer::class,
    ];

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin')->only(['store', 'update', 'destroy']);
    }

    public function list(Request $request, $resource = null)
    {
        if ($resource === null) {
            return response()->json([
                'api' => 'database',
                'resources' => array_keys(self::MODELS),
            ]);
        }

        $this->authorizeResourceRead($request, $resource);
        $model = $this->model($resource);
        $query = $model::query();

        $filters = $this->validateFilters($request, $resource);
        $query = $this->applyFilters($query, $resource, $filters);
        if ($resource === 'quizzes' && ! $request->user()->isAdmin()) {
            $query->whereIn('id', $this->publicQuizIds());
        }

        $perPage = (int) ($filters['per_page'] ?? 25);
        $results = $query->orderBy('id')->paginate($perPage)->through(
            fn ($item) => $this->format($resource, $item, $request),
        );

        return response()->json($results);
    }

    public function show(Request $request, $resource, $id)
    {
        $this->authorizeResourceRead($request, $resource);
        $model = $this->model($resource);
        $query = $model::query();
        if ($resource === 'quizzes' && ! $request->user()->isAdmin()) {
            $query->whereIn('id', $this->publicQuizIds());
        }
        $item = $query->find($id);

        return $item
            ? response()->json($this->format($resource, $item, $request))
            : response()->json(null, 404);
    }

    public function store(Request $request, $resource)
    {
        $model = $this->model($resource);
        $attrs = $this->attrs($resource, $request, false);
        if ($resource === 'quizzes') {
            $this->validateQuizMemberGroup($attrs);
        }
        if ($resource === 'answers') {
            $this->validateAnswerTarget($attrs);
        }

        if ($resource === 'quizzes') {
            $attrs['is_published'] = false;
        }

        if ($resource === 'questions') {
            $item = DB::transaction(function () use ($attrs): Question {
                $quiz = Quiz::query()->whereKey($attrs['quiz_id'])->lockForUpdate()->firstOrFail();
                $question = $quiz->questions()->create([
                    'text' => $attrs['text'],
                    'order' => ($quiz->questions()->max('order') ?? 0) + 1,
                    'meta' => $attrs['meta'] ?? null,
                ]);

                if ($quiz->is_published) {
                    $quiz->update(['is_published' => false]);
                }

                return $question;
            });
        } elseif ($resource === 'answers') {
            $question = Question::findOrFail($attrs['question_id']);
            $item = DB::transaction(function () use ($attrs, $question): Answer {
                $quiz = Quiz::query()->whereKey($question->quiz_id)->lockForUpdate()->firstOrFail();
                $answer = Answer::create($attrs);
                if ($quiz->is_published) {
                    $quiz->update(['is_published' => false]);
                }

                return $answer;
            });
        } else {
            $item = $model::create($attrs);
        }

        return response()->json($this->format($resource, $item, $request), 201);
    }

    public function update(Request $request, $resource, $id)
    {
        $model = $this->model($resource);
        $item = $model::find($id);

        if (! $item) {
            return response()->json(null, 404);
        }

        $attrs = $this->attrs($resource, $request, true);
        if ($resource === 'questions'
            && isset($attrs['quiz_id'])
            && (int) $attrs['quiz_id'] !== (int) $item->quiz_id) {
            throw ValidationException::withMessages(['quiz_id' => 'A question cannot be moved to another quiz through the API.']);
        }
        if ($resource === 'answers'
            && isset($attrs['question_id'])
            && (int) $attrs['question_id'] !== (int) $item->question_id) {
            throw ValidationException::withMessages(['question_id' => 'An answer cannot be moved to another question through the API.']);
        }
        if ($resource === 'quizzes') {
            $this->validateQuizMemberGroup(array_merge($item->only(['group_id', 'member_id']), $attrs));
        }
        if ($resource === 'answers') {
            $this->validateAnswerTarget($attrs, $item);
        }
        if (in_array($resource, ['questions', 'answers'], true)) {
            $question = $resource === 'questions' ? $item : $item->question;
            $quiz = $question?->quiz;
            if ($quiz) {
                DB::transaction(function () use ($quiz, $item, $attrs): void {
                    $lockedQuiz = Quiz::query()->whereKey($quiz->id)->lockForUpdate()->firstOrFail();
                    $item->update($attrs);
                    if ($lockedQuiz->is_published) {
                        $lockedQuiz->update(['is_published' => false]);
                    }
                });
            } else {
                $item->update($attrs);
            }
        } else {
            $item->update($attrs);
        }

        return response()->json($this->format($resource, $item->fresh(), $request));
    }

    public function destroy(MediaDeletionService $mediaDeletion, $resource, $id)
    {
        $model = $this->model($resource);
        $item = $model::find($id);

        if (! $item) {
            return response()->json(null, 404);
        }

        $mediaDeletion->delete($item);

        return response()->json(null, 204);
    }

    private function model(string $resource): string
    {
        abort_unless(isset(self::MODELS[$resource]), 404);

        return self::MODELS[$resource];
    }

    private function authorizeResourceRead(Request $request, string $resource): void
    {
        abort_unless(isset(self::MODELS[$resource]), 404);
        abort_if(in_array($resource, ['questions', 'answers'], true) && ! $request->user()->isAdmin(), 403);
    }

    private function validateFilters(Request $request, string $resource): array
    {
        $rules = [
            'page' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
        if (in_array($resource, ['members', 'albums', 'quizzes'], true)) {
            $rules['group_id'] = ['sometimes', 'integer', 'min:1'];
        }
        if ($resource === 'quizzes') {
            $rules['member_id'] = ['sometimes', 'integer', 'min:1'];
        }
        if ($resource === 'questions') {
            $rules['quiz_id'] = ['sometimes', 'integer', 'min:1'];
        }
        if ($resource === 'answers') {
            $rules['question_id'] = ['sometimes', 'integer', 'min:1'];
        }

        return $request->validate($rules);
    }

    private function applyFilters($query, string $resource, array $filters)
    {
        if (in_array($resource, ['members', 'albums', 'quizzes'], true) && isset($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }
        if ($resource === 'quizzes' && isset($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }
        if ($resource === 'questions' && isset($filters['quiz_id'])) {
            $query->where('quiz_id', $filters['quiz_id']);
        }
        if ($resource === 'answers') {
            if (isset($filters['question_id'])) {
                $query->where('question_id', $filters['question_id']);
            }
        }

        return $query;
    }

    private function validateQuizMemberGroup(array $attrs): void
    {
        if (empty($attrs['member_id'])) {
            return;
        }

        $member = Member::find($attrs['member_id']);
        if (! $member || empty($attrs['group_id']) || (int) $member->group_id !== (int) $attrs['group_id']) {
            throw ValidationException::withMessages([
                'member_id' => 'The selected member must belong to the selected group.',
            ]);
        }
    }

    private function validateAnswerTarget(array $attrs, ?Answer $existing = null): void
    {
        $meta = array_key_exists('meta', $attrs) ? ($attrs['meta'] ?? []) : ($existing?->meta ?? []);
        $targetType = $meta['target_type'] ?? null;
        $targetId = filter_var($meta['target_id'] ?? null, FILTER_VALIDATE_INT);
        $questionId = $attrs['question_id'] ?? $existing?->question_id;
        $question = Question::find($questionId);
        $quiz = $question?->quiz;

        $tables = ['group' => Group::class, 'member' => Member::class, 'album' => Album::class];
        $valid = $question && $quiz && $targetId && $targetId > 0 && isset($tables[$targetType]);
        if ($valid) {
            $target = $tables[$targetType]::find($targetId);
            $valid = (bool) $target;
            if ($valid && $quiz->group_id && $targetType === 'group') {
                $valid = (int) $target->id === (int) $quiz->group_id;
            }
            if ($valid && $quiz->group_id && in_array($targetType, ['member', 'album'], true)) {
                $valid = (int) $target->group_id === (int) $quiz->group_id;
            }
        }

        if (! $valid) {
            throw ValidationException::withMessages([
                'meta.target_id' => 'Select an existing target that belongs to this quiz.',
            ]);
        }
    }

    private function publicQuizIds(): array
    {
        $ids = [];
        Quiz::query()
            ->where('is_published', true)
            ->with('questions.answers')
            ->chunkById(100, function ($quizzes) use (&$ids): void {
                foreach ($quizzes as $quiz) {
                    if ($quiz->publicationErrors() === []) {
                        $ids[] = $quiz->id;
                    }
                }
            });

        return $ids;
    }

    private function attrs(string $resource, Request $request, bool $updating): array
    {
        $data = $request->validate($this->rules($resource, $updating));

        if ($resource === 'answers') {
            $meta = $data['meta'] ?? [];
            $metaChanged = array_key_exists('meta', $data);

            foreach (['target_type', 'target_id'] as $key) {
                if (array_key_exists($key, $data)) {
                    $meta[$key] = $data[$key];
                    unset($data[$key]);
                    $metaChanged = true;
                }
            }

            if ($metaChanged) {
                $data['meta'] = $meta ?: null;
            } elseif (! $updating) {
                $data['meta'] = null;
            }
        }

        return $data;
    }

    private function rules(string $resource, bool $updating): array
    {
        $required = $updating ? 'sometimes' : 'required';

        return match ($resource) {
            'groups' => [
                'name' => [$required, 'string', 'max:255'],
                'debut_date' => ['sometimes', 'nullable', 'date'],
                'concept' => ['sometimes', 'nullable', 'string'],
                'about' => ['sometimes', 'nullable', 'string'],
                'description' => ['sometimes', 'nullable', 'string'],
                'image' => ['sometimes', 'nullable', 'string', 'max:255'],
            ],
            'members' => [
                'group_id' => [$required, 'integer', 'exists:groups,id'],
                'name' => [$required, 'string', 'max:255'],
                'about' => ['sometimes', 'nullable', 'string'],
                'traits' => ['sometimes', 'nullable', 'array'],
                'description' => ['sometimes', 'nullable', 'string'],
                'image' => ['sometimes', 'nullable', 'string', 'max:255'],
            ],
            'albums' => [
                'group_id' => [$required, 'integer', 'exists:groups,id'],
                'title' => [$required, 'string', 'max:255'],
                'release_date' => ['sometimes', 'nullable', 'date'],
                'concept' => ['sometimes', 'nullable', 'string'],
                'vibe' => ['sometimes', 'nullable', 'array'],
                'concept_traits' => ['sometimes', 'nullable', 'array'],
                'description' => ['sometimes', 'nullable', 'string'],
                'image' => ['sometimes', 'nullable', 'string', 'max:255'],
            ],
            'quizzes' => [
                'group_id' => ['sometimes', 'nullable', 'integer', 'exists:groups,id'],
                'member_id' => ['sometimes', 'nullable', 'integer', 'exists:members,id'],
                'name' => [$required, 'string', 'max:255'],
                'settings' => ['sometimes', 'nullable', 'array'],
                'image' => ['sometimes', 'nullable', 'string', 'max:255'],
            ],
            'questions' => [
                'quiz_id' => [$required, 'integer', 'exists:quizzes,id'],
                'text' => [$required, 'string'],
                'order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
                'meta' => ['sometimes', 'nullable', 'array'],
            ],
            'answers' => [
                'question_id' => [$required, 'integer', 'exists:questions,id'],
                'text' => [$required, 'string'],
                'points' => ['sometimes', 'integer'],
                'meta' => ['sometimes', 'nullable', 'array'],
                'target_type' => ['sometimes', 'nullable', 'string', 'in:member,group,album'],
                'target_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            ],
            default => abort(404),
        };
    }

    private function format(string $resource, object $item, Request $request): array
    {
        $data = $item->toArray();

        if (! $request->user()->isAdmin()) {
            // Scoring internals are admin-only: answer meta/points, question meta, quiz settings.
            return match ($resource) {
                'answers' => array_intersect_key($data, array_flip(['id', 'question_id', 'text', 'created_at', 'updated_at'])),
                'questions' => array_intersect_key($data, array_flip(['id', 'quiz_id', 'text', 'order', 'created_at', 'updated_at'])),
                'quizzes' => array_intersect_key($data, array_flip(['id', 'group_id', 'member_id', 'name', 'image', 'created_at', 'updated_at'])),
                default => $data,
            };
        }

        if ($resource === 'answers') {
            $meta = $data['meta'] ?? [];
            $data['target_type'] = $meta['target_type'] ?? null;
            $data['target_id'] = $meta['target_id'] ?? null;
        }

        return $data;
    }
}
