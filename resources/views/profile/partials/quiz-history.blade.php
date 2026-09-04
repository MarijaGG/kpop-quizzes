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
                @else
                    <p class="history-result">Result: {{ $result->result_name ?? 'Not available' }}</p>
                @endif
            </div>
            <time class="muted" datetime="{{ $result->created_at->toIso8601String() }}">{{ $result->created_at->format('M j, Y') }}</time>
            <a href="{{ route('quizzes.start', $result->quiz_id) }}" class="btn btn-ghost">Retake</a>
        </article>
    @empty
        <div class="card">
            <h3>No quiz attempts yet</h3>
            <p class="muted" style="margin-top:.5rem;">Complete a quiz and your result will appear here.</p>
        </div>
    @endforelse
</section>
