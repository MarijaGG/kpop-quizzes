@extends('layouts.app')

@section('content')
<div class="container">
    <h1><strong>Admin panel</strong></h1>

    <ul>
        <li><a href="{{ route('admin.groups.index') }}">Groups</a></li>
        <li><a href="{{ route('admin.members.index') }}">Members</a></li>
        <li><a href="{{ route('admin.albums.index') }}">Albums</a></li>
        <li><a href="{{ route('admin.quizzes.index') }}">Quizzes</a></li>
    </ul>
</div>
@endsection
