@extends('layouts.app')

@section('content')
<div class="container max-w-6xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('admin.index') }}" class="px-3 py-2 bg-gray-100 border rounded hover:bg-gray-200">← Admin</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
        <h2 class="text-xl font-semibold mb-4">Edit Album</h2>

        <form method="post" action="{{ route('admin.albums.update', $album->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Image</label>
            @if($album->image)
                <div><img src="{{ asset('storage/'.$album->image) }}" style="height:64px;object-fit:cover;"/></div>
            @endif
            <input type="file" name="image" class="form-control" accept="image/*" />
        </div>

        <div class="mb-3">
            <label class="form-label">Group</label>
            <select name="group_id" class="form-control" required>
                @foreach($groups as $g)
                    <option value="{{ $g->id }}" {{ $g->id === $album->group_id ? 'selected' : '' }}>{{ $g->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Title</label>
            <input name="title" value="{{ old('title', $album->title) }}" class="form-control" required />
        </div>

        <div class="mb-3">
            <label class="form-label">Release Date</label>
            <input type="date" name="release_date" value="{{ old('release_date', $album->release_date) }}" class="form-control" />
        </div>

        <div class="mb-3">
            <label class="form-label">Concept</label>
            <input name="concept" value="{{ old('concept', $album->concept) }}" class="form-control" />
        </div>

        <h5>Concept Traits (up to 5)</h5>
        @php $ct = old('concept_traits', $album->concept_traits ?? []); @endphp
        @for($i=0;$i<5;$i++)
            <div class="mb-2">
                <input name="concept_traits[]" class="form-control" placeholder="Trait {{ $i+1 }}" value="{{ $ct[$i] ?? '' }}" />
            </div>
        @endfor

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control">{{ old('description', $album->description) }}</textarea>
        </div>

        <button class="btn btn-primary">Update Album</button>
    </form>
    </div>
</div>
@endsection
