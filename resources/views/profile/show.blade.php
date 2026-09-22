<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Profile') }}</h2>
    </x-slot>

    <div class="page-container">
        <div class="page-inner">
            <section class="profile-summary">
                <div class="profile-avatar">
                    @if($avatarMember && !empty($avatarMember['image']))
                        <img src="{{ asset('storage/'.$avatarMember['image']) }}" alt="{{ $avatarMember['name'] }}">
                    @else
                        <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="profile-summary-content">
                    <div class="profile-summary-heading">
                        <div>
                            <div class="profile-name-row">
                                <h1>{{ $user->name }}</h1>
                                @if($selectedTitle)
                                    <span class="profile-title" style="--title-hue: {{ $user->selectedTitleHue() }};">{{ $selectedTitle }}</span>
                                @endif
                            </div>
                            <p class="profile-bio">{{ $user->bio ?: 'No bio yet.' }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="btn btn-primary">Edit profile</a>
                    </div>
                </div>
            </section>

            @if(session('status') === 'profile-updated')
                <p class="profile-saved">Profile updated.</p>
            @endif

            @if(session('status') === 'favorites-updated')
                <p class="profile-saved">Favourites updated.</p>
            @endif

            @include('profile.partials.favourites')

            @include('profile.partials.quiz-history')
        </div>
    </div>
</x-app-layout>

<script>
    document.addEventListener('click', function (event) {
        const link = event.target.closest('.profile-history-pagination a');
        if (!link) return;

        event.preventDefault();
        loadQuizHistory(link.href, true);
    });

    window.addEventListener('popstate', function () {
        loadQuizHistory(window.location.href, false);
    });

    function loadQuizHistory(url, updateHistory) {
        const historySection = document.querySelector('.profile-history');
        if (!historySection) return;

        historySection.setAttribute('aria-busy', 'true');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => {
                if (!response.ok) throw new Error('Unable to load quiz history');
                return response.text();
            })
            .then(html => {
                const replacement = new DOMParser()
                    .parseFromString(html, 'text/html')
                    .querySelector('.profile-history');
                if (!replacement) throw new Error('Quiz history was not found');

                historySection.replaceWith(replacement);
                if (updateHistory) window.history.pushState({}, '', url);
            })
            .catch(() => historySection.removeAttribute('aria-busy'))
            .finally(() => {
                const currentSection = document.querySelector('.profile-history');
                currentSection?.removeAttribute('aria-busy');
            });
    }
</script>
