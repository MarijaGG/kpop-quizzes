@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner max-w-6xl mx-auto">
        <div class="mb-4">
            <a href="{{ route('admin.quizzes.questions.index', $quiz->id) }}" class="back-button">← Questions</a>
        </div>
        <div class="card card-medium">
            <h1 class="text-xl font-semibold mb-4">New question for {{ $quiz->name }}</h1>
            <form method="POST" action="{{ route('admin.quizzes.questions.store', $quiz->id) }}">
                @csrf
                <label for="question-text" class="form-label">Question text</label>
                <textarea id="question-text" name="text" class="form-control" rows="4" required maxlength="5000">{{ old('text') }}</textarea>
                @error('text')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
                <button type="submit" class="btn btn-primary mt-4">Create question</button>
            </form>
        </div>
    </div>
</div>
@endsection