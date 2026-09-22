@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <div class="card card-medium card-centered text-center">
            <h1 class="text-xl">Guess the Song — Results</h1>
            <p class="guess-final-score">{{ $run['score'] }}/{{ $max }} points</p>

            <div style="text-align:left;margin-top:1.5rem;">
                @foreach($run['responses'] as $response)
                    <div class="guess-song-result-item {{ $response['correct'] ? 'is-correct' : 'is-incorrect' }}">
                        <div>
                            <strong>{{ $response['correct'] ? 'Correct' : 'Incorrect' }}</strong>
                            <div class="guess-song-answer">{{ $response['correct'] ? ($response['guess'] ?: 'Correct answer') : ('Your answer: '.($response['guess'] ?: 'I don\'t know')) }}</div>
                            @if(!$response['correct'])
                                <div class="guess-song-answer">Correct answer: <strong>{{ $response['title'] }} — {{ $response['artist'] }}</strong></div>
                            @endif
                        </div>
                        @if($response['points'] > 0)
                            <span class="guess-song-points">+{{ $response['points'] }} pts</span>
                        @endif
                    </div>
                @endforeach
            </div>

            <div style="margin-top:1.5rem;">
                <a href="{{ route('guess-song.index') }}" class="btn btn-primary">Play again</a>
            </div>
        </div>
    </div>
</div>

@if(!empty($newTitle))
    <x-modal name="title-unlocked" :show="true" maxWidth="md" focusable class="title-unlock-modal">
        <div class="title-unlock-content text-center">
            <button type="button" class="title-unlock-close" aria-label="Close" x-on:click="$dispatch('close-modal', 'title-unlocked')">&times;</button>
            <h2 class="text-xl">New title unlocked</h2>
            <span class="profile-title mt-3" style="--title-hue: {{ crc32($newTitle['key']) % 360 }};">{{ $newTitle['label'] }}</span>
            <form
                method="post"
                action="{{ route('profile.title.equip') }}"
                class="mt-6"
                x-data="{ saving: false }"
                x-on:submit.prevent="
                    saving = true;
                    fetch($el.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: new FormData($el),
                    }).then(response => {
                        if (!response.ok) throw new Error();
                        $dispatch('close-modal', 'title-unlocked');
                    }).catch(() => saving = false);
                "
            >
                @csrf
                @method('patch')
                <input type="hidden" name="title" value="{{ $newTitle['key'] }}">
                <button type="submit" class="btn btn-primary" x-bind:disabled="saving">
                    <span x-text="saving ? 'Equipping...' : 'Equip now'"></span>
                </button>
            </form>
        </div>
    </x-modal>
@endif
@endsection
