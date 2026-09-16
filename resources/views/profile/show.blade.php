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
                            <div style="display:flex; align-items:flex-end; gap:.8rem; flex-wrap:wrap; margin-bottom:.5rem;">
                                <h1 style="margin:0; font-size:clamp(1.8rem, 3vw, 3rem); line-height:0.9; font-weight:900; letter-spacing:0.02em; font-family:Impact, 'Arial Black', sans-serif; background:linear-gradient(180deg, #ffffff 0%, #d9d9d9 32%, #a3a3a3 100%); -webkit-background-clip:text; background-clip:text; color:transparent; -webkit-text-stroke: 2.1px #111111; text-shadow: 0 0 12px rgba(255,255,255,0.45);">{{ $user->name }}</h1>
                                <span style="display:inline-block; padding:.8rem 1.2rem; border-radius:999px; background:linear-gradient(120deg, #f9f9f9 0%, #d4d4d4 20%, #a1a1a1 40%, #f3f3f3 60%, #6b7280 80%, #f4f4f4 100%); background-size:220% 220%; color:#111111; border:3px solid #111111; font-size:.8rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; box-shadow: inset 0 0 10px rgba(255,255,255,0.45), 0 4px 0 rgba(17,17,17,0.75); line-height:1; min-width: 180px; text-align:center;">
                                    Ni-ki's #1 fan
                                </span>
                            </div>
                            <div style="display:flex; flex-wrap:wrap; gap:.5rem; margin-top:.75rem;">
                                <span style="display:inline-block; padding:.35rem .7rem; border-radius:999px; background:linear-gradient(135deg, #fdf2f8 0%, #f9a8d4 35%, #ec4899 100%); color:#3b0a2d; border:1px solid rgba(190, 24, 93, 0.4); font-size:.62rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;">
                                    TXT expert
                                </span>
                                <span style="display:inline-block; padding:.35rem .7rem; border-radius:999px; background:#86efac; color:#052e16; border:1px solid rgba(22, 163, 74, 0.45); font-size:.62rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;">
                                    Iroha's twin
                                </span>
                                <span style="display:inline-block; padding:.35rem .7rem; border-radius:999px; background:linear-gradient(135deg, #eff6ff 0%, #bfdbfe 35%, #8b5cf6 100%); color:#1e1b4b; border:1px solid rgba(139, 92, 246, 0.4); font-size:.62rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;">
                                    HOP enjoyer
                                </span>
                            </div>
                            <p class="profile-bio" style="margin-top:.75rem;">{{ $user->bio ?: 'No bio yet.' }}</p>
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
