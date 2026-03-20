@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner max-w-6xl mx-auto">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('admin.quizzes.questions.index', $quiz_id) }}" class="back-button">← Questions</a>
        </div>

        <div class="card">
            <h2 class="text-xl font-semibold mb-2">Manage Answers for Question</h2>
            <p class="text-sm text-gray-600 mb-4">{{ $question->text ?? '' }}</p>

            <form method="post" action="{{ route('admin.quizzes.questions.update', [$quiz_id, $question->id]) }}" class="max-w-3xl mx-auto">
            @csrf

            <h5>Answers (up to 8)</h5>

        <div class="mb-3">
            <label class="form-label">Question target type</label>
            @php $qType = old('target_type', $question_target_type ?? 'member'); @endphp
            <select name="target_type" id="question-target-type" class="form-control">
                <option value="">--</option>
                <option value="group" {{ $qType == 'group' ? 'selected' : '' }}>Group</option>
                <option value="member" {{ $qType == 'member' ? 'selected' : '' }}>Member</option>
                <option value="album" {{ $qType == 'album' ? 'selected' : '' }}>Album</option>
            </select>
        </div>

        @for($i=0;$i<8;$i++)
            @php $existing = $answers[$i] ?? null; @endphp
            <div class="card mb-2">
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label">Answer {{ $i+1 }}</label>
                        <input type="text" name="answers[{{ $i }}][text]" class="form-control" value="{{ old('answers.'.$i.'.text', $existing->text ?? '') }}" />
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Target</label>
                        @php
                            $tId = old('answers.'.$i.'.target_id', $existing->target_id ?? $existing->member_id ?? '');
                            $tType = old('answers.'.$i.'.target_type', $existing->target_type ?? (!empty($existing->member_id) ? 'member' : ''));
                        @endphp
                        <select name="answers[{{ $i }}][target_id]" class="form-control target-id">
                            <option value="">--</option>
                            <optgroup label="Groups">
                                @foreach($groups as $g)
                                    <option data-type="group" value="{{ $g->id }}" {{ ($tType === 'group' && (string)$tId === (string)$g->id) ? 'selected' : '' }}>{{ $g->name }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Members">
                                @foreach($members as $m)
                                    <option data-type="member" value="{{ $m->id }}" {{ ($tType === 'member' && (string)$tId === (string)$m->id) ? 'selected' : '' }}>{{ $m->name }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Albums">
                                @foreach($albums as $a)
                                    <option data-type="album" value="{{ $a->id }}" {{ ($tType === 'album' && (string)$tId === (string)$a->id) ? 'selected' : '' }}>{{ $a->title ?? 'Album '.$a->id }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                        <input type="hidden" name="answers[{{ $i }}][target_type]" value="{{ $tType }}" class="answer-target-type" />
                    </div>
                </div>
            </div>
        @endfor

                <button class="btn btn-primary">Save Answers</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var top = document.getElementById('question-target-type');
    function filterAll(){
        var val = top ? top.value : '';
        document.querySelectorAll('.target-id').forEach(function(idsel){
            idsel.querySelectorAll('option').forEach(function(opt){
                var t = opt.getAttribute('data-type');
                if (!t) { opt.style.display = ''; opt.disabled = false; return; }
                if (val === '' || t === val) { opt.style.display = ''; opt.disabled = false; }
                else { opt.style.display = 'none'; opt.disabled = true; }
            });
        });
    }
    if (top) { top.addEventListener('change', filterAll); }
    filterAll();
});
</script>

@endsection
