@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <div class="card card-medium card-centered">
            <h1 class="text-xl font-semibold">Guess the Song</h1>
            <p class="muted" style="margin-top:.5rem;">
                Listen to a short clip and type the song title or artist. 
            </p>
            @error('songs')<p class="text-red-600 text-sm" style="margin-top:.75rem;">{{ $message }}</p>@enderror
            <form method="POST" action="{{ route('guess-song.start') }}" style="margin-top:1.25rem;">
                @csrf
                <button class="btn btn-primary" {{ $available < $rounds ? 'disabled' : '' }}>Start game</button>
            </form>
        </div>
    </div>
</div>
@endsection
