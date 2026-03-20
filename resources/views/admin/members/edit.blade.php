@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner max-w-6xl mx-auto">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('admin.members.index') }}" class="back-button">← Members</a>
        </div>

        <div class="card">
            <h2 class="text-xl font-semibold mb-4">Edit Member</h2>

            <form method="post" action="{{ route('admin.members.update', $member->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

        <div class="mb-3">
            <label class="form-label">Image</label>
            @if($member->image)
                <div><img src="{{ asset('storage/'.$member->image) }}" style="height:64px;object-fit:cover;"/></div>
            @endif
            <input type="file" name="image" class="form-control" accept="image/*" />
        </div>

        <div class="mb-3">
            <label class="form-label">Group</label>
            <select name="group_id" class="form-control" required>
                @foreach($groups as $g)
                    <option value="{{ $g->id }}" {{ (string)$g->id === (string)($member->group_id ?? '') ? 'selected' : '' }}>{{ $g->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input name="name" value="{{ old('name', $member->name) }}" class="form-control" required />
        </div>

        <div class="mb-3">
            <label class="form-label">About</label>
            <textarea name="about" class="form-control">{{ old('about', $member->about) }}</textarea>
        </div>

        <h5>Traits (up to 5)</h5>
        @php $traits = old('traits', $member->traits ?? []); @endphp
        @for($i=0;$i<5;$i++)
            <div class="mb-2">
                <input name="traits[]" class="form-control" placeholder="Trait {{ $i+1 }}" value="{{ $traits[$i] ?? '' }}" />
            </div>
        @endfor

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control">{{ old('description', $member->description) }}</textarea>
        </div>

                <button class="btn btn-primary">Update Member</button>
            </form>
        </div>
    </div>
</div>
@endsection
