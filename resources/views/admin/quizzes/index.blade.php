@extends('layouts.app')

@section('content')
<div class="container max-w-6xl mx-auto">
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('admin.index') }}" class="px-3 py-2 bg-gray-100 border rounded hover:bg-gray-200">← Admin</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
        <h2 class="text-xl font-semibold mb-4">Quizzes <a class="btn btn-sm btn-primary" href="{{ route('admin.quizzes.create') }}">New</a></h2>

    <table class="table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Group</th>
                <th>Member</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($quizzes as $q)
            <tr>
                <td>
                    @if($q->image)
                        <img src="{{ asset('storage/'.$q->image) }}" alt="{{ $q->name }}" style="height:48px;object-fit:cover;" />
                    @endif
                </td>
                <td>{{ $q->name }}</td>
                <td>{{ $q->group->name ?? '' }}</td>
                <td>{{ $q->member->name ?? '' }}</td>
                <td>
                    <a class="btn btn-sm btn-secondary" href="{{ route('admin.quizzes.edit', $q->id) }}">Edit</a>
                    <a class="btn btn-sm btn-info" href="{{ route('admin.quizzes.questions.index', $q->id) }}">Questions</a>
                    <form method="POST" action="{{ route('admin.quizzes.destroy', $q->id) }}" style="display:inline-block" onsubmit="return confirm('Delete this quiz?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

        {{ $quizzes->links() }}
    </div>
</div>
@endsection
