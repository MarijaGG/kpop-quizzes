@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('admin.index') }}" class="back-button">← Admin</a>
            <a href="{{ route('admin.guess-songs.create') }}" class="btn btn-primary">Add song</a>
        </div>

        <div class="card">
            <h1 class="text-xl font-semibold mb-4">Guess Songs</h1>

            <div class="guess-image-grid">
                @forelse($songs as $song)
                    <article class="guess-image-card">
                        <audio controls src="{{ asset('storage/'.$song->audio) }}" style="width:100%;"></audio>
                        <div class="guess-image-card-copy">
                            <strong>{{ $song->title }}</strong>
                            <span>{{ $song->artist }}</span>
                            <div class="guess-image-actions">
                                <a href="{{ route('admin.guess-songs.edit', $song) }}" class="btn btn-ghost">Edit</a>
                                <form method="POST" action="{{ route('admin.guess-songs.destroy', $song) }}" onsubmit="return confirm('Delete this song?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="muted">No songs added yet.</p>
                @endforelse
            </div>
            {{ $songs->links() }}
        </div>
    </div>
</div>
@endsection
