@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <a href="{{ route('admin.guess-songs.index') }}" class="back-button">← Guess Songs</a>
        <div class="card card-medium" style="margin-top:1rem;">
            <h1 class="text-xl font-semibold mb-4">Edit Song</h1>
            @include('admin.guess-songs.form', ['song' => $song])
        </div>
    </div>
</div>
@endsection
