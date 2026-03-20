@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner max-w-6xl mx-auto">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('admin.quizzes.index') }}" class="back-button">← Quizzes</a>
        </div>

        <div class="card">
            <h2>Create Quiz</h2>

            <form method="post" action="{{ route('admin.quizzes.store') }}" enctype="multipart/form-data">
                @csrf
        <div class="mb-3">
            <label class="form-label">Image</label>
            <input type="file" name="image" class="form-control" accept="image/*" required />
        </div>
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input name="name" class="form-control" required />
        </div>
        <div class="mb-3">
            <label class="form-label">Group (optional)</label>
            <select name="group_id" class="form-control">
                <option value="">--</option>
                @foreach($groups as $g)
                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Member (optional)</label>
            <select name="member_id" class="form-control">
                <option value="">--</option>
                @php
                    $groupsById = collect($groups)->keyBy('id');
                @endphp
                @foreach($members as $m)
                    @php $gname = $groupsById[$m->group_id]->name ?? ''; @endphp
                    <option value="{{ $m->id }}">{{ $m->name }} {{ $gname ? '('.$gname.')' : '' }}</option>
                @endforeach
            </select>
        </div>

        <h5>10 Questions</h5>
        @for($i=0;$i<10;$i++)
            <div class="mb-2">
                <label class="form-label">Question {{ $i+1 }}</label>
                <textarea name="questions[]" class="form-control" required></textarea>
            </div>
        @endfor

                <button class="btn btn-primary" type="submit">Create Quiz</button>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var form = document.querySelector('form[enctype="multipart/form-data"]');
    if (!form) return;
    var file = form.querySelector('input[type="file"][name="image"]');
    var btn = form.querySelector('button[type="submit"], button.btn-primary');
    function update(){ if (btn) btn.disabled = !file || !file.value; }
    if (file) { file.addEventListener('change', update); update(); }
});
</script>
@endsection
