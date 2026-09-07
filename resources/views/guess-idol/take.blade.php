@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <div class="card card-centered guess-question-card">
            <div class="question-meta">Question {{ $index + 1 }} of {{ $total }}</div>
            <img src="{{ asset('storage/'.$question['image']) }}" alt="Guess the idol" class="guess-question-image">
            <form method="POST" action="{{ route('guess-idol.answer') }}">
                @csrf
                <div class="guess-answer-grid">
                    @foreach($options as $option)
                        <label class="answer-label">
                            <input type="radio" name="choice" value="{{ $option['id'] }}" required>
                            <span>{{ $option['name'] }}</span>
                        </label>
                    @endforeach
                </div>
                <button class="btn btn-primary" style="margin-top:1rem;">{{ $index + 1 === $total ? 'Finish quiz' : 'Next' }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
