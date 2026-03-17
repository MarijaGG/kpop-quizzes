<nav x-data="{ open: false }" class="site-nav">
    <!-- Primary Navigation Menu -->
    <div class="wrap">
        <div class="nav-inner">
            <div class="nav-left">
                <!-- Site title -->
                <div class="site-title-wrap">
                    <a href="{{ route('dashboard') }}" class="site-title">K-pop quizzes</a>
                </div>

                <!-- Navigation Links -->
                <div class="nav-links">
                    <x-nav-link :href="route('quizzes.index')" :active="request()->routeIs('quizzes.*')">
                        {{ __('Quizzes') }}
                    </x-nav-link>
                </div>
            </div>

            <div class="nav-right">
                @if(Auth::user() && Auth::user()->isAdmin())
                    <div class="admin-link">
                        <a href="{{ route('admin.index') }}">Admin panel</a>
                    </div>
                @endif
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="dropdown-trigger">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>
