<x-guest-layout>
    @if (Route::has('login'))
        <main class="flex-1 flex flex-col items-center justify-center gap-6 text-center">
            <h1 class="welcome-title">Kpop Quizzes</h1>
            <p class="muted text-sm max-w-md">Many quizzes to discover your K-pop matches.</p>

            <div class="hero-cta controls layout-flex gap-2 mt-4">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-ghost">Register</a>
                    @endif
                @endauth
            </div>
        </main>
    @endif
</x-guest-layout>
