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
