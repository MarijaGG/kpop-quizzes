@extends('layouts.app')

@section('content')
<div class="container max-w-6xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('admin.index') }}" class="px-3 py-2 bg-gray-100 border rounded hover:bg-gray-200">← Admin</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
        <h2 class="text-xl font-semibold mb-4">Edit Group</h2>

        <form method="post" action="{{ route('admin.groups.update', $group->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Image</label>
            @if($group->image)
                <div><img src="{{ asset('storage/'.$group->image) }}" style="height:64px;object-fit:cover;"/></div>
            @endif
            <input type="file" name="image" class="form-control" accept="image/*" />
        </div>

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input name="name" value="{{ old('name', $group->name) }}" class="form-control" required />
        </div>
        <div class="mb-3">
            <label class="form-label">Debut Date</label>
            <input type="date" name="debut_date" value="{{ old('debut_date', $group->debut_date) }}" class="form-control" />
        </div>
        <div class="mb-3">
            <label class="form-label">Concept / Vibe</label>
            <input name="concept" value="{{ old('concept', $group->concept) }}" class="form-control" />
        </div>
        <div class="mb-3">
            <label class="form-label">About</label>
            <textarea name="about" class="form-control">{{ old('about', $group->about) }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control">{{ old('description', $group->description) }}</textarea>
        </div>
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
