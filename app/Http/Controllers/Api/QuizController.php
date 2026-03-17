<?php

namespace App\Http\Controllers\Api;

use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends ApiController
{
    public function index()
    {
        return $this->success(Quiz::with('questions')->get());
    }

    public function show(Quiz $quiz)
    {
        $quiz->load('questions.answers');
        return $this->success($quiz);
    }

    public function store(Request $request)
    {
        $attrs = $request->validate([
            'title' => 'required|string',
            'group_id' => 'nullable|exists:groups,id',
            'description' => 'nullable|string',
        ]);

        $quiz = Quiz::create($attrs);
        return $this->success($quiz, 201);
    }
}
