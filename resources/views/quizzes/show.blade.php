@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <div class="card card-centered show-wide">
            <div class="show-top">
                @if(!empty($quiz->image))
                    <img src="{{ asset('storage/'.$quiz->image) }}" alt="{{ $quiz->name }}" class="show-thumb" />
                @endif

                <div class="title-block">
                    <h1 class="text-xl">{{ $quiz->name }}</h1>
                    <p class="desc-text">{{ $quiz->description ?? '' }}</p>
                    <div class="mt-4">
                        <a href="{{ route('quizzes.start', $quiz->id) }}" class="btn btn-primary">Start Quiz</a>
                    </div>
                </div>
            </div>

            @php
                $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
                $quizStats = $json['quiz_stats'][$quiz->id] ?? [];
                $members = $json['members'] ?? [];
                $groups = $json['groups'] ?? [];
                $albums = $json['albums'] ?? [];
            @endphp

            <div class="results-card">
                @if(!empty($quizStats))
                    <div class="card">
                        <h4>How others scored</h4>
                        @if(!empty($quizStats['percent_buckets']))
                            <ul class="result-list">
                                @foreach(['0-30','31-60','61-90','91-100'] as $b)
                                    <li class="result-row">{{ $b }}: {{ $quizStats['percent_buckets'][$b] ?? 0 }}</li>
                                @endforeach
                            </ul>
                        @else
                            @php
                                $displayType = null;
                                foreach(['member','group','album'] as $t) { if(!empty($quizStats[$t])) { $displayType = $t; break; } }
                                $counts = $displayType ? $quizStats[$displayType] : [];
                                $totalVotes = array_sum(array_values($counts));
                                $rows = [];
                                if($displayType) {
                                    foreach($counts as $key => $cnt) {
                                        $ent = null;
                                        if($displayType === 'member') { foreach($members as $m) { if((string)($m['id'] ?? '') === (string)$key) { $ent = (object)$m; break; } } }
                                        if($displayType === 'group') { foreach($groups as $g) { if((string)($g['id'] ?? '') === (string)$key) { $ent = (object)$g; break; } } }
                                        if($displayType === 'album') { foreach($albums as $a) { if((string)($a['id'] ?? '') === (string)$key) { $ent = (object)$a; break; } } }
                                        $rows[] = ['entity' => $ent, 'count' => $cnt, 'id' => $key];
                                    }
                                    usort($rows, function($a,$b){ return ($b['count'] ?? 0) <=> ($a['count'] ?? 0); });
                                }
                            @endphp

                            @if(!empty($rows))
                                <ul class="result-list">
                                    @foreach($rows as $r)
                                        @php
                                            $ent = $r['entity'];
                                            $count = $r['count'] ?? 0;
                                            $pct = $totalVotes ? round(($count / $totalVotes) * 100) : 0;
                                            $img = $ent->image ?? null;
                                        @endphp
                                        <li class="result-row">
                                            <div class="result-row-top">
                                                <div class="result-left">
                                                    @if(!empty($img))<img src="{{ asset('storage/'.$img) }}" class="result-thumb"/>@endif
                                                    <div class="result-label">{{ $ent->name ?? $ent->title ?? 'Item' }}</div>
                                                </div>
                                                <div class="muted">{{ $count }} results • {{ $pct }}%</div>
                                            </div>
                                            <div class="bar-bg">
                                                <div class="bar" style="width: {{ $pct }}%;"></div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="muted">No result stats yet.</div>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
