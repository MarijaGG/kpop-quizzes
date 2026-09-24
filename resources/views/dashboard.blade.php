<x-app-layout>
    <x-slot name="header">
        <h1 class="page-header">Welcome, {{ auth()->user() ? auth()->user()->name : 'Guest' }}</h1>
    </x-slot>

    <div class="page-container">
        <div class="page-inner">
            <div class="dashboard-actions">
                <a href="{{ route('quizzes.index') }}" class="dashboard-action-card dashboard-action-card-quizzes">
                    <span class="dashboard-action-title">Quizzes <span aria-hidden="true">↗</span></span>
                    <span class="dashboard-action-description">Explore personality and knowledge quizzes.</span>
                </a>
                <a href="{{ route('guess-idol.index') }}" class="dashboard-action-card dashboard-action-card-idol">
                    <span class="dashboard-action-title">Guess the Idol <span aria-hidden="true">↗</span></span>
                    <span class="dashboard-action-description">Identify idols from detail images.</span>
                </a>
                <a href="{{ route('guess-song.index') }}" class="dashboard-action-card dashboard-action-card-song">
                    <span class="dashboard-action-title">Guess the Song <span aria-hidden="true">↗</span></span>
                    <span class="dashboard-action-description">Listen to clips and name the song.</span>
                </a>
            </div>

            <!-- Recently added quizzes section -->
            <div class="card mt-6">
                <div class="card-header card-header-flex">
                    <h3 class="card-title">Recent quizzes</h3>
                </div>

                <div class="card-body">
                    @php
                        $recent = $recentQuizzes;
                    @endphp

                    <div class="recent-gallery">
                        @forelse($recent as $quiz)
                            <div class="recent-item">
                                @php
                                    $id = is_array($quiz) ? ($quiz['id'] ?? null) : ($quiz->id ?? null);
                                    $title = is_array($quiz) ? ($quiz['name'] ?? ($quiz['title'] ?? '')) : ($quiz->name ?? $quiz->title ?? '');
                                    $desc = is_array($quiz) ? ($quiz['description'] ?? '') : ($quiz->description ?? '');
                                    $img = is_array($quiz) ? ($quiz['image'] ?? '') : ($quiz->image ?? '');
                                    $routeId = $id;
                                @endphp
                                <a href="{{ is_numeric($routeId) ? route('quizzes.show', $routeId) : route('quizzes.index') }}" class="recent-link">
                                    @php
                                        $imgUrl = null;
                                        if(!empty($img)) {
                                            $imgUrl = preg_match('/^https?:\/\//', $img) ? $img : asset('storage/'.$img);
                                        }
                                    @endphp
                                    @if(!empty($imgUrl))
                                        <div class="recent-thumb">
                                            <img src="{{ $imgUrl }}" alt="{{ $title }}" />
                                        </div>
                                    @else
                                        <div class="recent-thumb-placeholder"></div>
                                    @endif

                                    <div class="recent-meta">
                                        <div class="recent-title">{{ $title }}</div>
                                    </div>
                                </a>
                            </div>
                        @empty
                            <div>No recent quizzes found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
