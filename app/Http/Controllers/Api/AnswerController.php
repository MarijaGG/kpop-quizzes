<?php

namespace App\Http\Controllers\Api;

use App\Models\Answer;
use Illuminate\Http\Request;

class AnswerController extends ApiController
{
    public function index()
    {
        return $this->success(Answer::with('question')->get());
    }

    public function show(Answer $answer)
    {
        $answer->load('question');
        return $this->success($answer);
    }

    public function store(Request $request)
    {
        $attrs = $request->validate([
            'question_id' => 'required|exists:questions,id',
            'text' => 'required|string',
            'is_correct' => 'nullable|boolean',
        ]);

        $answer = Answer::create($attrs);
        return $this->success($answer, 201);
    }
}
