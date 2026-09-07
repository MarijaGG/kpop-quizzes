@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <div class="card card-medium card-centered text-center">
            <h1 class="text-xl">Guess the {{ $group['name'] ?? 'Idol' }} Member</h1>
            <p class="muted" style="margin-top:.35rem;">{{ ucfirst($run['difficulty']) }}</p>
            <p class="guess-final-score">{{ $run['score'] }}/{{ count($run['questions']) }} correct</p>
            <div style="margin-top:1rem;">
                <a href="{{ route('guess-idol.index') }}" class="btn btn-primary">Play again</a>
            </div>
        </div>
    </div>
</div>
@endsection
