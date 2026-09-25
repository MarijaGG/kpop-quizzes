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
