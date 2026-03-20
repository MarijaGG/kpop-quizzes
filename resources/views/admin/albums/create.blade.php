@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner max-w-6xl mx-auto">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('admin.albums.index') }}" class="back-button">← Albums</a>
        </div>

        <div class="card">
            <h2 class="text-xl font-semibold mb-4">Create Album</h2>

            <form method="post" action="{{ route('admin.albums.store') }}" enctype="multipart/form-data">
            @csrf
        <div class="mb-3">
            <label class="form-label">Image</label>
            <input type="file" name="image" class="form-control" accept="image/*" required />
        </div>
        <div class="mb-3">
            <label class="form-label">Group</label>
            <select name="group_id" class="form-control" required>
                <option value="">Select group</option>
                @foreach($groups as $g)
                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input name="title" class="form-control" required />
        </div>
        <div class="mb-3">
            <label class="form-label">Release Date</label>
            <input type="date" name="release_date" class="form-control" />
        </div>
        <div class="mb-3">
            <label class="form-label">Concept</label>
            <input name="concept" class="form-control" />
        </div>

        <h5>Concept Traits (up to 5)</h5>
        @for($i=0;$i<5;$i++)
            <div class="mb-2">
                <input name="concept_traits[]" class="form-control" placeholder="Trait {{ $i+1 }}" />
            </div>
        @endfor

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>

            <button class="btn btn-primary" type="submit">Create Album</button>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var form = document.querySelector('form[enctype="multipart/form-data"]');
    if (!form) return;
    var file = form.querySelector('input[type="file"][name="image"]');
    var btn = form.querySelector('button[type="submit"], button.btn-primary');
    function update(){ if (btn) btn.disabled = !file || !file.value; }
    if (file) { file.addEventListener('change', update); update(); }
});
</script>
@endsection
