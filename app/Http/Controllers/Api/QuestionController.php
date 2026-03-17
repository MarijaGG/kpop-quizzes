<?php

namespace App\Http\Controllers\Api;

use App\Models\Question;
use Illuminate\Http\Request;

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

        $question = Question::create($attrs);
        return $this->success($question, 201);
    }
}
