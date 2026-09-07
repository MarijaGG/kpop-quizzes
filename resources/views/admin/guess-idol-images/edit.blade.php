@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="page-inner">
        <a href="{{ route('admin.guess-idol-images.index') }}" class="back-button">← Guess Idol Images</a>
        <div class="card card-medium" style="margin-top:1rem;">
            <h1 class="text-xl font-semibold mb-4">Edit Guess Idol Image</h1>
            @include('admin.guess-idol-images.form', ['image' => $image])
        </div>
    </div>
</div>
@endsection
