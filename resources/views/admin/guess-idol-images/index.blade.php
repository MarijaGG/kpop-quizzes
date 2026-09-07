@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('admin.index') }}" class="back-button">← Admin</a>
            <a href="{{ route('admin.guess-idol-images.create') }}" class="btn btn-primary">Add quiz image</a>
        </div>

        <div class="card">
            <h1 class="text-xl font-semibold mb-4">Guess Idol Images</h1>
            <form method="GET" class="filter-form" style="margin-bottom:1rem;">
                <select name="group_id" class="filter-select">
                    <option value="">All groups</option>
                    @foreach($groups as $group)
                        <option value="{{ $group['id'] }}" @selected((string) $groupId === (string) $group['id'])>{{ $group['name'] }}</option>
                    @endforeach
                </select>
                <select name="difficulty" class="filter-select">
                    <option value="">All difficulties</option>
                    @foreach(['easy', 'medium', 'hard'] as $level)
                        <option value="{{ $level }}" @selected($difficulty === $level)>{{ ucfirst($level) }}</option>
                    @endforeach
                </select>
                <button class="btn btn-ghost">Filter</button>
            </form>

            <div class="guess-image-grid">
                @forelse($images as $image)
                    <article class="guess-image-card">
                        <img src="{{ asset('storage/'.$image->image) }}" alt="{{ $image->member_name }}">
                        <div class="guess-image-card-copy">
                            <strong>{{ $image->member_name }}</strong>
                            <span>{{ $image->group_name }} · {{ ucfirst($image->difficulty) }}</span>
                            <div class="guess-image-actions">
                                <a href="{{ route('admin.guess-idol-images.edit', $image) }}" class="btn btn-ghost">Edit</a>
                                <form method="POST" action="{{ route('admin.guess-idol-images.destroy', $image) }}" onsubmit="return confirm('Delete this image?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="muted">No image-bank records found.</p>
                @endforelse
            </div>
            {{ $images->links() }}
        </div>
    </div>
</div>
@endsection
