<?php

namespace App\Http\Controllers\Api;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionController extends ApiController
{
    public function index()
    {
        return $this->success(Question::with('answers')->get());
    }

    public function show(Question $question)
    {
        $question->load('answers');

        return $this->success($question);
    }

    public function store(Request $request)
    {
        $attrs = $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'text' => 'required|string',
        ]);

        $question = DB::transaction(function () use ($attrs): Question {
            $quiz = Quiz::query()->whereKey($attrs['quiz_id'])->lockForUpdate()->firstOrFail();
            $attrs['order'] = ($quiz->questions()->max('order') ?? 0) + 1;
            $question = $quiz->questions()->create(['text' => $attrs['text'], 'order' => $attrs['order']]);

            if ($quiz->is_published) {
                $quiz->update(['is_published' => false]);
            }

            return $question;
        });

        return $this->success($question, 201);
    }
}
