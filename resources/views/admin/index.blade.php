@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <h1 class="page-header">Admin panel</h1>

        <div class="admin-grid mt-4">
            <a href="{{ route('admin.groups.index') }}" class="admin-tile">
                <div class="admin-title">Groups</div>
                <div class="admin-desc">Manage groups, edit metadata and visibility.</div>
            </a>

            <a href="{{ route('admin.members.index') }}" class="admin-tile">
                <div class="admin-title">Members</div>
                <div class="admin-desc">Add or edit member profiles and photos.</div>
            </a>

            <a href="{{ route('admin.albums.index') }}" class="admin-tile">
                <div class="admin-title">Albums</div>
                <div class="admin-desc">Create and manage albums.</div>
            </a>

            <a href="{{ route('admin.quizzes.index') }}" class="admin-tile">
                <div class="admin-title">Quizzes</div>
                <div class="admin-desc">Manage quizzes, questions and results.</div>
            </a>
        </div>
    </div>
</div>
@endsection
