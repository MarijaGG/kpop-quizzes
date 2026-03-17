@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <div class="controls" style="margin-bottom:1rem;">
            <a href="{{ route('quizzes.start', $quiz_id) }}" class="btn btn-primary">Retake Quiz</a>
        </div>

        @if(!empty($result) && $resultType === 'percent')
            @php
                $memberObj = null;
                foreach($members as $m) { if((int)($m['id'] ?? 0) === (int)($result->member_id ?? 0)) { $memberObj = (object)$m; break; } }
                $percent = (int)($result->percent ?? 0);
                if ($percent <= 30) {
                    $msg = 'You should get to know them better...';
                } elseif ($percent <= 60) {
                    $msg = 'You know some things about them, but there\'s room to learn more.';
                } elseif ($percent <= 90) {
                    $msg = 'You know them well!';
                } else {
                    $msg = 'Perfect — you know them inside out!';
                }
            @endphp
            <div class="card text-center">
                <h2 class="text-large">You know {{ $percent }}% about {{ $memberObj->name ?? 'them' }}</h2>
                <p class="muted" style="margin-top:0.5rem;">{{ $msg }}</p>
            </div>
            @if(!empty($quizStats) && !empty($quizStats['percent_buckets']))
                <div class="card" style="margin-top:1rem;padding:1rem;">
                    <h4>How others scored</h4>
                    <ul class="result-list">
                        @foreach(['0-30','31-60','61-90','91-100'] as $b)
                            <li class="result-row">{{ $b }}: {{ $quizStats['percent_buckets'][$b] ?? 0 }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @else
            <div class="layout-grid {{ isset($resultType) && in_array($resultType, ['album','group']) ? 'align-center' : '' }}">
                <div class="card card-centered">
                    @if(!empty($result) && !empty($resultType))
                        @if($resultType === 'member')
                            <h2 class="text-xl">You are: {{ $result->name }}</h2>
                            <p class="result-desc" style="margin-top:0.5rem;">{{ $result->description ?? '' }}</p>
                            @if(!empty($result->traits) && is_array($result->traits))
                                <div class="mt-3">
                                    <h4>Traits</h4>
                                    <div class="traits-list">
                                        @foreach($result->traits as $t)
                                            <span class="trait-item">{{ $t }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <h3 class="mt-4">About</h3>
                            <p class="about-text">{!! nl2br(e($result->about ?? '')) !!}</p>
                        @elseif($resultType === 'group')
                            <h2 class="text-xl">{{ $result->name }}</h2>
                            <p class="result-desc">{{ $result->description ?? '' }}</p>
                            <h3 class="mt-4">About</h3>
                            <p class="about-text">{!! nl2br(e($result->about ?? '')) !!}</p>
                        @elseif($resultType === 'album')
                            <h2 class="text-xl">Album: {{ $result->title ?? ($result->name ?? 'Album') }}</h2>
                            <p class="result-desc" style="margin-top:0.5rem;">{{ $result->description ?? '' }}</p>
                            @php $albumTraits = $result->traits ?? $result->concept_traits ?? []; @endphp
                            @if(!empty($albumTraits) && is_array($albumTraits))
                                <div class="mt-3">
                                        <h4>Traits</h4>
                                        <div class="traits-list">
                                            @foreach($albumTraits as $t)
                                                <span class="trait-item">{{ $t }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                            @endif
                        @endif
                    @else
                        <h2>No result could be determined</h2>
                    @endif
                </div>

                <div class="card image-card">
                    @if(!empty($result) && !empty($result->image))
                        <div class="text-center w-full">
                            <img src="{{ asset('storage/'.$result->image) }}" class="result-img" alt="{{ $result->name ?? ($result->title ?? '') }}" />
                            @if(!empty($resultType) && $resultType === 'album' && !empty($result->release_date))
                                <div class="muted" style="margin-top:0.5rem;">Release date: {{ date('F j, Y', strtotime($result->release_date)) }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            @if(!empty($quizStats) && !empty($candidates))
                <div class="card" style="margin-top:1rem;">
                    <h4>Other possible results</h4>
                    @php
                        $counts = $quizStats[$resultType] ?? [];
                        $totalVotes = array_sum(array_values($counts));
                        $rows = [];
                        foreach($candidates as $c) {
                            $id = (string)($c->id ?? $c->id);
                            $cnt = isset($counts[$id]) ? $counts[$id] : 0;
                            $rows[] = ['candidate' => $c, 'count' => $cnt];
                        }
                        usort($rows, function($a,$b){ return ($b['count'] ?? 0) <=> ($a['count'] ?? 0); });
                    @endphp
                    <ul class="result-list">
                        @foreach($rows as $r)
                            @php
                                $c = $r['candidate'];
                                $count = $r['count'] ?? 0;
                                $pct = $totalVotes ? round(($count / $totalVotes) * 100) : 0;
                                $resultId = (string)($result->id ?? $result->member_id ?? '');
                                $candidateId = (string)($c->id ?? $c->id);
                                $isCurrent = $resultType !== 'percent' && $candidateId !== '' && $resultId !== '' && $candidateId === $resultId;
                                $barClass = $isCurrent ? 'bar-current' : 'bar';
                                $labelClass = $isCurrent ? 'label-current' : 'label-normal';
                            @endphp
                            <li class="result-row">
                                <div class="result-row-top">
                                    <div class="result-left">
                                        @if(!empty($c->image))<img src="{{ asset('storage/'.$c->image) }}" class="result-thumb"/>@endif
                                        <div class="result-label {{ $labelClass }}">{{ $c->name ?? $c->title ?? 'Item' }}</div>
                                    </div>
                                    <div class="muted">{{ $count }} results • {{ $pct }}%</div>
                                </div>
                                <div class="bar-bg">
                                    <div class="{{ $barClass }} bar" style="width: {{ $pct }}%;"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
