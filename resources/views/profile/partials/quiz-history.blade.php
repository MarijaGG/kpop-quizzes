<section class="profile-history">
    <div class="profile-section-heading">
        <h2>Quiz history</h2>
    </div>

    @forelse($results as $result)
        @php
            $details = $result->details ?? [];
            $quizName = $result->quiz_name ?? $quizNames[(string) $result->quiz_id] ?? ($details['quiz_name'] ?? 'Quiz');
            $resultType = $result->result_type ?? (($details['quiz_type'] ?? null) === 'percent' ? 'knowledge' : 'personality');
        @endphp
        <article class="history-item">
            <div class="history-quiz">
                <h3>{{ $quizName }}</h3>
                @if($resultType === 'knowledge')
                    <p class="history-result">Score: {{ $result->total_points }}%</p>
                    <span class="muted">Questions: {{ $result->correct_answers ?? $details['correct'] ?? 0 }}/{{ $result->total_questions ?? $details['total'] ?? 0 }} correct</span>
                @elseif($resultType === 'guess_idol')
                    <p class="history-result">Score: {{ $result->correct_answers }}/{{ $result->total_questions }} correct</p>
                @elseif($resultType === 'guess_song')
                    <p class="history-result">Score: {{ $result->total_points }} points ({{ $result->correct_answers }}/{{ $result->total_questions }} correct)</p>
                @else
                    <p class="history-result">Result: {{ $result->result_name ?? 'Not available' }}</p>
                @endif
            </div>
            <time class="muted" datetime="{{ $result->created_at->toIso8601String() }}">{{ $result->created_at->format('M j, Y') }}</time>
            <a href="{{ match($resultType) { 'guess_idol' => route('guess-idol.index'), 'guess_song' => route('guess-song.index'), default => route('quizzes.start', $result->quiz_id) } }}" class="btn btn-ghost">Retake</a>
        </article>
    @empty
        <div class="card">
            <h3>No quiz attempts yet</h3>
            <p class="muted" style="margin-top:.5rem;">Complete a quiz and your result will appear here.</p>
        </div>
    @endforelse

    @if($results->hasPages())
        <div class="profile-history-pagination">
            <nav aria-label="Quiz history pages">
                <div>
                    @php
                        $lastPage = $results->lastPage();
                        $currentPage = $results->currentPage();
                        $firstPage = max(1, min($currentPage - 2, $lastPage - 4));
                        $lastVisiblePage = min($lastPage, $firstPage + 4);
                    @endphp
                    @if($currentPage > 1)
                        <a href="{{ $results->url(1) }}" aria-label="First page" title="First page">&laquo;</a>
                        <a href="{{ $results->previousPageUrl() }}" aria-label="Previous page" title="Previous page">&lsaquo;</a>
                    @else
                        <span aria-disabled="true"><span>&laquo;</span></span>
                        <span aria-disabled="true"><span>&lsaquo;</span></span>
                    @endif
                    @for($page = $firstPage; $page <= $lastVisiblePage; $page++)
                        @if($page === $results->currentPage())
                            <span aria-current="page"><span>{{ $page }}</span></span>
                        @else
                            <a href="{{ $results->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor
                    @if($currentPage < $lastPage)
                        <a href="{{ $results->nextPageUrl() }}" aria-label="Next page" title="Next page">&rsaquo;</a>
                        <a href="{{ $results->url($lastPage) }}" aria-label="Last page" title="Last page">&raquo;</a>
                    @else
                        <span aria-disabled="true"><span>&rsaquo;</span></span>
                        <span aria-disabled="true"><span>&raquo;</span></span>
                    @endif
                </div>
            </nav>
        </div>
    @endif
</section>
