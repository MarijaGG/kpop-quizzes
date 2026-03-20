@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner max-w-6xl mx-auto">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('admin.index') }}" class="back-button">← Admin</a>

            @php
                $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
                $allGroups = array_map(function($i){ return (object)$i; }, $json['groups'] ?? []);
                $selectedGroup = request('group_id');
            @endphp
            <div class="flex-1 flex justify-center">
                <form method="GET" action="{{ route('admin.members.index') }}" class="flex items-center gap-2">
                    <select name="group_id" class="px-3 py-2 border rounded">
                        <option value="">All groups</option>
                        @foreach($allGroups as $g)
                            <option value="{{ $g->id }}" {{ (string)$selectedGroup === (string)$g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-ghost">Filter</button>
                </form>
            </div>

            <a href="{{ route('admin.members.create') }}" class="btn btn-primary">New</a>
        </div>
        <div class="card">
            <h2 class="text-xl font-semibold mb-4">Members</h2>

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
