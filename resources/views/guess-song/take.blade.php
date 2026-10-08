@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <div class="card card-centered guess-question-card">
            <div class="question-meta">Song {{ $index + 1 }} of {{ $total }}</div>
            <p class="tier-badge" style="margin-top:.5rem;">{{ ucfirst($tier) }} — {{ $tierPoints }} point{{ $tierPoints === 1 ? '' : 's' }}</p>

            <audio id="clip" preload="none"></audio>
            <div style="display:flex;align-items:center;gap:.75rem;margin-top:1rem;">
                <button type="button" id="play-clip" class="btn btn-ghost" style="flex:0 0 auto;">▶ Play ({{ $tierSeconds }}s)</button>
                <div style="flex:1 1 auto;height:6px;border-radius:999px;background:var(--brand-2);overflow:hidden;">
                    <div id="clip-progress-bar" style="height:100%;width:0%;background:var(--brand-1);"></div>
                </div>
            </div>

            <form id="guess-form" method="POST" action="{{ route('guess-song.answer') }}" style="margin-top:1.25rem;">
                @csrf
                <div class="favourite-autocomplete">
                    <input type="text" id="guess-input" class="favourite-search" style="width:100%;" autocomplete="off" placeholder="Type a guess and press Enter">
                    <div id="guess-search-results" class="favourite-suggestions" role="listbox" aria-label="Matching songs" hidden></div>
                </div>
                <input type="hidden" id="guess-value" name="guess" value="{{ old('guess') }}">
                <p id="guess-search-status" class="muted text-sm mt-2" role="status"></p>
                <div id="selected-guess-option" class="mt-2" aria-live="polite" @if(!old('guess')) hidden @endif>
                    @if(old('guess'))
                        <span class="btn btn-ghost">{{ old('guess') }}</span>
                    @endif
                </div>
                @error('guess')<p class="text-red-600 text-sm" style="margin-top:.5rem;">{{ $message }}</p>@enderror
                <div style="display:flex;gap:.5rem;margin-top:1rem;">
                    <button type="submit" id="submit-guess" class="btn btn-primary">Submit guess</button>
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
        const form = document.getElementById('guess-form');
        const guessInput = document.getElementById('guess-input');
        const guessValue = document.getElementById('guess-value');
        const selectedOption = document.getElementById('selected-guess-option');
        const searchResults = document.getElementById('guess-search-results');
        const searchStatus = document.getElementById('guess-search-status');
        const seconds = {{ $tierSeconds }};
        const clipUrl = @json(route('guess-song.clip'));
        let timer = null;

        function showSelectedGuess(value) {
            guessValue.value = value;
            selectedOption.replaceChildren();
            selectedOption.hidden = false;

            const option = document.createElement('span');
            option.className = 'btn btn-ghost';
            option.textContent = value;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-ghost';
            remove.textContent = '×';
            remove.setAttribute('aria-label', 'Remove selected guess');
            remove.addEventListener('click', function () {
                guessValue.value = '';
                selectedOption.replaceChildren();
                selectedOption.hidden = true;
                guessInput.focus();
            });

            selectedOption.append(option, remove);
        }

        guessInput.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const value = guessInput.value.trim();
            if (!value) return;
            if (value.length < 2) {
                searchStatus.textContent = 'Type at least two characters to search.';
                return;
            }

            searchStatus.textContent = 'Searching…';
            searchResults.replaceChildren();
            searchResults.hidden = true;

            fetch(@json(route('guess-song.search')) + '?q=' + encodeURIComponent(value), {
                headers: { 'Accept': 'application/json' },
            }).then(function (response) {
                if (!response.ok) throw new Error('Search failed');
                return response.json();
            }).then(function (matches) {
                searchStatus.textContent = matches.length ? 'Choose a song:' : 'No matching songs found.';
                if (!matches.length) return;

                matches.forEach(function (match) {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'favourite-suggestion';
                    option.setAttribute('role', 'option');
                    option.textContent = `${match.title} — ${match.artist}`;
                    option.addEventListener('click', function () {
                        showSelectedGuess(match.title);
                        guessInput.value = '';
                        searchResults.replaceChildren();
                        searchResults.hidden = true;
                        searchStatus.textContent = '';
                    });
                    searchResults.appendChild(option);
                });
                searchResults.hidden = false;
            }).catch(function () {
                searchStatus.textContent = 'Song search is unavailable. Please try again.';
            });
        });

        form.addEventListener('submit', function (event) {
            if (event.submitter?.name === 'skip' || guessValue.value.trim()) return;
            event.preventDefault();
            guessInput.focus();
        });

        if (guessValue.value.trim()) showSelectedGuess(guessValue.value.trim());

        button.addEventListener('click', function () {
            clearTimeout(timer);
            bar.style.transition = 'none';
            bar.style.width = '0%';
            void bar.offsetWidth;
            button.disabled = true;

            if (!audio.src) {
                audio.src = clipUrl;
                audio.load();
            } else {
                audio.currentTime = 0;
            }

            audio.play().then(function () {
                bar.style.transition = `width ${seconds}s linear`;
                bar.style.width = '100%';
                timer = setTimeout(function () {
                    audio.pause();
                    button.disabled = false;
                }, seconds * 1000);
            }).catch(function () {
                button.disabled = false;
                alert('The audio clip could not be played. Please try again.');
            });
        });

    });
</script>
@endsection
