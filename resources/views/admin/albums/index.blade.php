@extends('layouts.app')

@section('content')
<div class="container max-w-6xl mx-auto">
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('admin.index') }}" class="px-3 py-2 bg-gray-100 border rounded hover:bg-gray-200">← Admin</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
        <h2 class="text-xl font-semibold mb-4">Albums <a class="btn btn-sm btn-primary" href="{{ route('admin.albums.create') }}">New</a></h2>

    <table class="table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Release</th>
                <th>Concept</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($albums as $a)
            <tr>
                <td>
                    @if($a->image)
                        <img src="{{ asset('storage/'.$a->image) }}" alt="{{ $a->title }}" style="height:48px;object-fit:cover;" />
                    @endif
                </td>
                <td>{{ $a->title }}</td>
                <td>{{ $a->release_date }}</td>
                <td>{{ $a->concept }}</td>
                <td>
                    <a class="btn btn-sm btn-secondary" href="{{ route('admin.albums.edit', $a->id) }}">Edit</a>
                    <form method="POST" action="{{ route('admin.albums.destroy', $a->id) }}" style="display:inline-block" onsubmit="return confirm('Delete this album?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

        {{ $albums->links() }}
    </div>
</div>
@endsection
