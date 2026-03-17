@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
    <div class="card card-centered card-medium">
        <div class="question-meta">Question {{ $index + 1 }} of {{ $total }}</div>
        <h2 class="question-text">{{ $question->text }}</h2>

        <form method="post" action="{{ route('quizzes.answer', $quiz_id) }}">
            @csrf
            <div class="mt-4">
                @foreach($question->answers as $a)
                    <div class="answer-item">
                        <label class="answer-label">
                            <input type="radio" name="choice" value="{{ $a['id'] }}" required />
                            <span>{{ $a['text'] }}</span>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="mt-4" style="display:flex; justify-content:space-between; align-items:center;">
                <span></span>
                <button class="btn btn-primary">Next</button>
            </div>
        </form>
    </div>
    </div>
</div>
@endsection
