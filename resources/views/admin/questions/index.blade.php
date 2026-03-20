@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner max-w-6xl mx-auto">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('admin.index') }}" class="back-button">← Admin</a>
            <a href="{{ route('admin.quizzes.questions.create', $quiz->id ?? request()->segment(3)) }}" class="btn btn-primary">New</a>
        </div>
        <div class="card">
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
