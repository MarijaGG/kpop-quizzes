@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <div class="card card-medium card-centered">
            <h1 class="text-xl font-semibold">Guess the Idol</h1>
            <p class="muted" style="margin-top:.5rem;">Choose a group and difficulty, then identify five detail images.</p>
            <form method="POST" action="{{ route('guess-idol.start') }}" style="margin-top:1.25rem;">
                @csrf
                @php
                    $availableByGroup = $available->groupBy('group_id');
                @endphp
                <fieldset class="guess-choice-section">
                    <legend class="form-label">Choose a group</legend>
                    <div class="guess-choice-grid">
                        @foreach($groups as $group)
                            @if($availableByGroup->has($group['id']))
                                <label class="guess-choice-card">
                                    <input type="radio" name="group_id" value="{{ $group['id'] }}" required>
                                    <span>{{ $group['name'] }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </fieldset>
                @error('group_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror

                <fieldset class="guess-choice-section">
                    <legend class="form-label">Choose difficulty</legend>
                    <div class="guess-choice-grid guess-difficulty-grid">
                        @foreach(['easy', 'medium', 'hard'] as $difficulty)
                            <label class="guess-choice-card guess-difficulty-choice" data-difficulty="{{ $difficulty }}">
                                <input type="radio" name="difficulty" value="{{ $difficulty }}" required disabled>
                                <span>{{ ucfirst($difficulty) }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                @error('difficulty')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror

                <button class="btn btn-primary" style="margin-top:1.25rem;">Start quiz</button>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const availability = @json($available->groupBy('group_id')->map(fn ($items) => $items->pluck('difficulty')->values()->all()));
        const groupInputs = document.querySelectorAll('input[name="group_id"]');
        const difficultyChoices = document.querySelectorAll('.guess-difficulty-choice');

        groupInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                const availableDifficulties = availability[input.value] || [];

                difficultyChoices.forEach(function (choice) {
                    const difficultyInput = choice.querySelector('input');
                    const enabled = availableDifficulties.includes(difficultyInput.value);
                    difficultyInput.disabled = !enabled;
                    choice.hidden = !enabled;
                    if (!enabled) difficultyInput.checked = false;
                });
            });
        });
    });
</script>
@endsection
