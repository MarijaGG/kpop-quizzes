@extends('layouts.app')

@section('content')
<div class="container max-w-6xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('admin.index') }}" class="px-3 py-2 bg-gray-100 border rounded hover:bg-gray-200">← Admin</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
        <h2 class="text-xl font-semibold mb-4">Create Group</h2>

        <form method="post" action="{{ route('admin.groups.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">Image</label>
            <input type="file" name="image" class="form-control" accept="image/*" />
        </div>
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input name="name" class="form-control" required />
        </div>
        <div class="mb-3">
            <label class="form-label">Debut Date</label>
            <input type="date" name="debut_date" class="form-control" />
        </div>
        <div class="mb-3">
            <label class="form-label">Concept / Vibe</label>
            <input name="concept" class="form-control" />
        </div>
        <div class="mb-3">
            <label class="form-label">About</label>
            <textarea name="about" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <button class="btn btn-primary">Create</button>
    </form>
</div>
@endsection
