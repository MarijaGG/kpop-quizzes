@extends('layouts.app')

@section('content')
<div class="container max-w-6xl mx-auto">
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('admin.index') }}" class="px-3 py-2 bg-gray-100 border rounded hover:bg-gray-200">← Admin</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200">
        <h2 class="text-xl font-semibold mb-4">Questions for: {{ $quiz->name ?? 'Quiz' }}</h2>

    <table class="table">
        <thead>
            <tr>
                <th>Order</th>
                <th>Text</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($questions as $q)
            <tr>
                <td>{{ $q->order }}</td>
                <td>{{ $q->text }}</td>
                <td>
                    <a class="btn btn-sm btn-secondary" href="{{ route('admin.quizzes.questions.edit', [$quiz->id ?? request()->segment(3), $q->id]) }}">Manage Answers</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

        {{ $questions->links() }}
    </div>
</div>
@endsection
