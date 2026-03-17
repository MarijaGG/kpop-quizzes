@extends('layouts.app')

@section('content')
<div class="container max-w-6xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('admin.index') }}" class="px-3 py-2 bg-gray-100 border rounded hover:bg-gray-200">← Admin</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
        <h2 class="text-xl font-semibold mb-4">Create Album</h2>

        <form method="post" action="{{ route('admin.albums.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">Image</label>
            <input type="file" name="image" class="form-control" accept="image/*" />
        </div>
        <div class="mb-3">
            <label class="form-label">Group</label>
            <select name="group_id" class="form-control" required>
                <option value="">Select group</option>
                @foreach($groups as $g)
                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input name="title" class="form-control" required />
        </div>
        <div class="mb-3">
            <label class="form-label">Release Date</label>
            <input type="date" name="release_date" class="form-control" />
        </div>
        <div class="mb-3">
            <label class="form-label">Concept</label>
            <input name="concept" class="form-control" />
        </div>

        <h5>Concept Traits (up to 5)</h5>
        @for($i=0;$i<5;$i++)
            <div class="mb-2">
                <input name="concept_traits[]" class="form-control" placeholder="Trait {{ $i+1 }}" />
            </div>
        @endfor

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>

        <button class="btn btn-primary">Create Album</button>
    </form>
</div>
@endsection
