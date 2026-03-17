@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Create Quiz</h2>

    <form method="post" action="{{ route('admin.quizzes.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">Image (optional)</label>
            <input type="file" name="image" class="form-control" accept="image/*" />
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
                @foreach($members as $m)
                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->group->name ?? '' }})</option>
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

        <button class="btn btn-primary">Create Quiz</button>
    </form>
</div>
@endsection
