<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'member_id',
        'name',
        'settings',
        'image',
        'is_published',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_published' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function publicationErrors(): array
    {
        $questions = $this->relationLoaded('questions')
            ? $this->questions
            : $this->questions()->with('answers')->get();
        $errors = [];
        $answers = $questions->flatMap(fn (Question $question) => $question->relationLoaded('answers')
            ? $question->answers
            : $question->answers()->get());
        $targetIds = ['group' => [], 'member' => [], 'album' => []];

        foreach ($answers as $answer) {
            $meta = $answer->meta ?? [];
            $type = $meta['target_type'] ?? null;
            $id = filter_var($meta['target_id'] ?? null, FILTER_VALIDATE_INT);
            if ($id && $id > 0 && isset($targetIds[$type])) {
                $targetIds[$type][] = $id;
            }
        }

        $validTargetIds = [
            'group' => $targetIds['group']
                ? Group::whereIn('id', array_unique($targetIds['group']))->pluck('id')->map(fn ($id) => (int) $id)->all()
                : [],
            'member' => $targetIds['member']
                ? Member::query()->whereIn('id', array_unique($targetIds['member']))
                    ->when($this->group_id, fn ($query) => $query->where('group_id', $this->group_id))
                    ->pluck('id')->map(fn ($id) => (int) $id)->all()
                : [],
            'album' => $targetIds['album']
                ? Album::query()->whereIn('id', array_unique($targetIds['album']))
                    ->when($this->group_id, fn ($query) => $query->where('group_id', $this->group_id))
                    ->pluck('id')->map(fn ($id) => (int) $id)->all()
                : [],
        ];

        if ($questions->count() < 10) {
            $errors[] = 'A quiz needs at least 10 questions before it can be published.';
        }

        foreach ($questions as $question) {
            $label = 'Question '.$question->order;
            if (trim($question->text) === '') {
                $errors[] = "{$label} needs question text.";

                continue;
            }

            $questionAnswers = $question->relationLoaded('answers') ? $question->answers : $question->answers()->get();
            $validAnswers = $questionAnswers->filter(function (Answer $answer) use ($validTargetIds): bool {
                if (trim($answer->text) === '') {
                    return false;
                }

                $meta = $answer->meta ?? [];
                $type = $meta['target_type'] ?? null;
                $id = filter_var($meta['target_id'] ?? null, FILTER_VALIDATE_INT);
                if (! $id || $id < 1) {
                    return false;
                }

                return isset($validTargetIds[$type]) && in_array($id, $validTargetIds[$type], true);
            });

            if ($validAnswers->count() < 2) {
                $errors[] = "{$label} needs at least two answers with valid targets.";
            }
        }

        return $errors;
    }
}
