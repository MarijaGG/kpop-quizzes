@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner max-w-6xl mx-auto">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('admin.quizzes.index') }}" class="back-button">← Quizzes</a>
        </div>

        <div class="card">
            <h2>Edit Quiz</h2>

            <form method="post" action="{{ route('admin.quizzes.update', $quiz->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

        <div class="mb-3">
            <label class="form-label">Image</label>
            @if($quiz->image)
                <div><img src="{{ asset('storage/'.$quiz->image) }}" style="height:64px;object-fit:cover;"/></div>
            @endif
            <input type="file" name="image" class="form-control" accept="image/*" />
        </div>

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input name="name" value="{{ old('name', $quiz->name) }}" class="form-control" required />
        </div>

        <div class="mb-3">
            <label class="form-label">Group (optional)</label>
            <select name="group_id" class="form-control">
                <option value="">--</option>
                @foreach($groups as $g)
                    <option value="{{ $g->id }}" {{ $g->id == $quiz->group_id ? 'selected' : '' }}>{{ $g->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Member (optional)</label>
            <select name="member_id" class="form-control">
                <option value="">--</option>
                @foreach($members as $m)
                    @php
                        $mGroupName = '';
                        foreach($groups as $gg) { if((isset($gg->id) ? $gg->id : ($gg['id'] ?? null)) == (isset($m->group_id) ? $m->group_id : ($m['group_id'] ?? null))) { $mGroupName = isset($gg->name) ? $gg->name : ($gg['name'] ?? ''); break; } }
                    @endphp
                    <option value="{{ $m->id }}" {{ $m->id == $quiz->member_id ? 'selected' : '' }}>{{ $m->name }} ({{ $mGroupName }})</option>
                @endforeach
            </select>
        </div>

        <h5>Questions</h5>
        @php $existingQuestions = $questions ?? []; @endphp
        @for($i=0;$i<10;$i++)
            <div class="mb-2">
                <label class="form-label">Question {{ $i+1 }}</label>
                <textarea name="questions[]" class="form-control" required>{{ old('questions.'.$i, $existingQuestions[$i]->text ?? '') }}</textarea>
            </div>
        @endfor

                <button class="btn btn-primary">Update Quiz & Questions</button>
            </form>
        </div>
    </div>
</div>
@endsection
