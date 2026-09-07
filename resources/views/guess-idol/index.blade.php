@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <div class="card card-medium card-centered">
            <h1 class="text-xl font-semibold">Guess the Idol</h1>
            <p class="muted" style="margin-top:.5rem;">Choose a group and difficulty, then identify five detail images.</p>
            <form method="POST" action="{{ route('guess-idol.start') }}" style="margin-top:1.25rem;">
                @csrf
                <label class="form-label">Group</label>
                <select name="group_id" class="form-control" required>
                    <option value="">Choose a group</option>
                    @foreach($groups as $group)
                        <option value="{{ $group['id'] }}">{{ $group['name'] }}</option>
                    @endforeach
                </select>
                @error('group_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror

                <label class="form-label" style="display:block;margin-top:1rem;">Difficulty</label>
                <select name="difficulty" class="form-control" required>
                    <option value="">Choose difficulty</option>
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                </select>
                @error('difficulty')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror

                <button class="btn btn-primary" style="margin-top:1.25rem;">Start quiz</button>
            </form>
        </div>
    </div>
</div>
@endsection
