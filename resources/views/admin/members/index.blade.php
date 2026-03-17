@extends('layouts.app')

@section('content')
<div class="container max-w-6xl mx-auto">
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('admin.index') }}" class="px-3 py-2 bg-gray-100 border rounded hover:bg-gray-200">← Admin</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
        <h2 class="text-xl font-semibold mb-4">Members <a class="btn btn-sm btn-primary" href="{{ route('admin.members.create') }}">New</a></h2>

    <table class="table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($members as $m)
            <tr>
                <td>
                    @if($m->image)
                        <img src="{{ asset('storage/'.$m->image) }}" alt="{{ $m->name }}" style="height:48px;object-fit:cover;" />
                    @endif
                </td>
                <td>{{ $m->name }}</td>
                <td>
                    <a class="btn btn-sm btn-secondary" href="{{ route('admin.members.edit', $m->id) }}">Edit</a>
                    <form method="POST" action="{{ route('admin.members.destroy', $m->id) }}" style="display:inline-block" onsubmit="return confirm('Delete this member?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

        {{ $members->links() }}
    </div>
</div>
@endsection
