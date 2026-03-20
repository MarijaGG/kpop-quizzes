@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner max-w-6xl mx-auto">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('admin.groups.index') }}" class="back-button">← Groups</a>
        </div>

        <div class="card">
            <h2 class="text-xl font-semibold mb-4">Create Group</h2>

            <form method="post" action="{{ route('admin.groups.store') }}" enctype="multipart/form-data">
            @csrf
        <div class="mb-3">
            <label class="form-label">Image</label>
            <input type="file" name="image" class="form-control" accept="image/*" required />
        </div>
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input name="name" class="form-control" required />
        </div>
        <div class="mb-3">
            <label class="form-label">Debut Date</label>
            <input type="date" name="debut_date" class="form-control" />
        </div>
        <div class="mb-3">
            <label class="form-label">Concept / Vibe</label>
            <input name="concept" class="form-control" />
        </div>
        <div class="mb-3">
            <label class="form-label">About</label>
            <textarea name="about" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
            <button class="btn btn-primary" type="submit">Create</button>
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
