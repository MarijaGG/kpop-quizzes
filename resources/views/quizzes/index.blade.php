@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">

    <form method="get" class="filter-form">
        <label class="muted">Filter by group:</label>
        <select name="group" onchange="this.form.submit()" class="filter-select">
            <option value="">All</option>
            <option value="none" {{ (isset($groupFilter) && $groupFilter === 'none') ? 'selected' : '' }}>None</option>
            @if(!empty($groups))
                @foreach($groups as $g)
                    <option value="{{ $g->id }}" {{ (isset($groupFilter) && (string)$groupFilter === (string)$g->id) ? 'selected' : '' }}>{{ $g->name }}</option>
                @endforeach
            @endif
        </select>
        <noscript><button class="btn btn-primary">Filter</button></noscript>
    </form>
    <br>
    <div class="quiz-grid">
        @foreach($quizzes as $q)
            <a href="{{ route('quizzes.show', $q->id) }}" class="quiz-card">
                @if(!empty($q->image))
                    <img src="{{ asset('storage/'.$q->image) }}" class="quiz-image" alt="{{ $q->name }}" />
                @endif
                <h3 class="text-xl">{{ $q->name }}</h3>
                <p class="desc-text">{{ $q->description ?? '' }}</p>
            </a>
        @endforeach
    </div>
    </div>
</div>
@endsection
