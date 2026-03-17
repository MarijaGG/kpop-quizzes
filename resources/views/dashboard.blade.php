<x-app-layout>
    <x-slot name="header">
        <h1 class="page-header">Welcome, {{ auth()->user() ? auth()->user()->name : 'Guest' }}</h1>
    </x-slot>

    <div class="page-container">
        <div class="page-inner">
            <!-- Large full-width CTA card -->
            <div class="card hero-card">
                <div class="card-content">
                    <div class="hero-inner">
                        <div class="hero-cta">
                            <a href="{{ route('quizzes.index') }}" class="hero-link">Go to all quizzes →</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recently added quizzes section -->
            <div class="card mt-6">
                <div class="card-header card-header-flex">
                    <h3 class="card-title">Recent quizzes</h3>
                </div>

                <div class="card-body">
                    @php
                        $recent = $recentQuizzes ?? \App\Models\Quiz::orderBy('created_at','desc')->take(5)->get();

                            if(($recent ?? null) === null || count($recent) === 0) {
                                $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
                                $q = $json['quizzes'] ?? [];
                                // take latest by id if created_at missing
                                usort($q, function($a,$b){ return ($b['id'] ?? 0) <=> ($a['id'] ?? 0); });
                                $recent = array_slice($q, 0, 5);
                            }
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
