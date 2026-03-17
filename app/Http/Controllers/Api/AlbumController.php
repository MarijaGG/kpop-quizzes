<?php

namespace App\Http\Controllers\Api;

use App\Models\Album;
use Illuminate\Http\Request;

class AlbumController extends ApiController
{
    public function index()
    {
        return $this->success(Album::with('group')->get());
    }

    public function show(Album $album)
    {
        $album->load('group');
        return $this->success($album);
    }

    public function store(Request $request)
    {
        $attrs = $request->validate([
            'title' => 'required|string',
            'group_id' => 'required|exists:groups,id',
            'released_at' => 'nullable|date',
            'image' => 'nullable|string',
        ]);

        $album = Album::create($attrs);
        return $this->success($album, 201);
    }
}
