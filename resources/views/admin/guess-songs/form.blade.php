@php($editing = $song !== null)
<form method="POST" action="{{ $editing ? route('admin.guess-songs.update', $song) : route('admin.guess-songs.store') }}" enctype="multipart/form-data">
    @csrf
    @if($editing)
        @method('PUT')
    @endif

    <div class="mb-3">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" value="{{ old('title', $song->title ?? '') }}" required>
        @error('title')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Artist</label>
        <input type="text" name="artist" class="form-control" value="{{ old('artist', $song->artist ?? '') }}" required>
        @error('artist')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Audio file</label>
        @if($editing)
            <audio controls src="{{ asset('storage/'.$song->audio) }}" style="display:block;margin-bottom:.75rem;width:100%;"></audio>
        @endif
        <input type="file" name="audio" class="form-control" accept="audio/*" {{ $editing ? '' : 'required' }}>
        @error('audio')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <button class="btn btn-primary" type="submit">{{ $editing ? 'Save changes' : 'Save' }}</button>
</form>
