@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <div class="card card-centered guess-question-card">
            <div class="question-meta">Song {{ $index + 1 }} of {{ $total }}</div>
            <p class="tier-badge" style="margin-top:.5rem;">{{ ucfirst($tier) }} — {{ $tierPoints }} point{{ $tierPoints === 1 ? '' : 's' }}</p>

            <audio id="clip" src="{{ asset('storage/'.$audio) }}" preload="auto"></audio>
            <div style="display:flex;align-items:center;gap:.75rem;margin-top:1rem;">
                <button type="button" id="play-clip" class="btn btn-ghost" style="flex:0 0 auto;">▶ Play ({{ $tierSeconds }}s)</button>
                <div style="flex:1 1 auto;height:6px;border-radius:999px;background:var(--brand-2);overflow:hidden;">
                    <div id="clip-progress-bar" style="height:100%;width:0%;background:var(--brand-1);"></div>
                </div>
            </div>

            <form method="POST" action="{{ route('guess-song.answer') }}" style="margin-top:1.25rem;">
                @csrf
                <div class="favourite-autocomplete" data-autocomplete>
                    <input type="text" id="guess-input" name="guess" class="favourite-search" style="width:100%;" autocomplete="off" placeholder="Start typing..." required>
                    <div class="favourite-suggestions" data-suggestions hidden></div>
                </div>
                <div style="display:flex;gap:.5rem;margin-top:1rem;">
                    <button type="submit" class="btn btn-primary">Submit guess</button>
                    <button type="submit" name="skip" value="1" class="btn btn-ghost" formnovalidate>I don't know</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const audio = document.getElementById('clip');
        const button = document.getElementById('play-clip');
        const bar = document.getElementById('clip-progress-bar');
        const seconds = {{ $tierSeconds }};
        let timer = null;
        button.addEventListener('click', function () {
            audio.currentTime = 0;
            audio.play();
            clearTimeout(timer);
            bar.style.transition = 'none';
            bar.style.width = '0%';
            void bar.offsetWidth;
            bar.style.transition = `width ${seconds}s linear`;
            bar.style.width = '100%';
            timer = setTimeout(function () { audio.pause(); }, seconds * 1000);
        });

        const titles = @json($titles);
        const autocomplete = document.querySelector('[data-autocomplete]');
        const input = document.getElementById('guess-input');
        const suggestions = autocomplete.querySelector('[data-suggestions]');

        function renderSuggestions() {
            const query = input.value.trim().toLowerCase();
            suggestions.innerHTML = '';
            suggestions.hidden = false;

            titles.filter(title => title.toLowerCase().includes(query)).forEach(function (title) {
                const suggestion = document.createElement('button');
                suggestion.type = 'button';
                suggestion.className = 'favourite-suggestion';
                suggestion.textContent = title;
                suggestion.addEventListener('click', function () {
                    input.value = title;
                    suggestions.hidden = true;
                });
                suggestions.appendChild(suggestion);
            });
        }

        input.addEventListener('focus', renderSuggestions);
        input.addEventListener('input', renderSuggestions);
        document.addEventListener('click', function (event) {
            if (!autocomplete.contains(event.target)) suggestions.hidden = true;
        });
    });
</script>
@endsection
