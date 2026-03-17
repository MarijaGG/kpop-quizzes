@extends('layouts.app')

@section('content')
<div class="container max-w-6xl mx-auto">
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('admin.index') }}" class="px-3 py-2 bg-gray-100 border rounded hover:bg-gray-200">← Admin</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
        <h2 class="text-xl font-semibold mb-4">Groups <a class="btn btn-sm btn-primary" href="{{ route('admin.groups.create') }}">New</a></h2>

    <table class="table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Debut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($groups as $g)
            <tr>
                <td>
                    @if($g->image)
                        <img src="{{ asset('storage/'.$g->image) }}" alt="{{ $g->name }}" style="height:48px;object-fit:cover;" />
                    @endif
                </td>
                <td>{{ $g->name }}</td>
                <td>{{ $g->debut_date }}</td>
                <td>
                    <a class="btn btn-sm btn-secondary" href="{{ route('admin.groups.edit', $g->id) }}">Edit</a>
                    <form method="POST" action="{{ route('admin.groups.destroy', $g->id) }}" style="display:inline-block" onsubmit="return confirm('Delete this group?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

        {{ $groups->links() }}
    </div>
</div>
@endsection
