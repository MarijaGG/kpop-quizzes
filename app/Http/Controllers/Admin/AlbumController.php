<?php

namespace App\Http\Controllers\Admin;

use App\Models\Album;
use App\Models\Group;
use App\Services\MediaDeletionService;
use App\Support\UploadedFileReplacement;
use Illuminate\Http\Request;

class AlbumController extends BaseAdminController
{
    public function index(Request $request)
    {
        $query = Album::with('group')->latest();
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

return view('admin.albums.index', ['albums' => $query->paginate(20)->withQueryString()]);
    }

    public function create()
    {
        return view('admin.albums.create', ['groups' => Group::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['vibe'] = array_slice(array_values(array_filter($data['vibe'] ?? [])), 0, 3);
        $data['concept_traits'] = array_slice(array_values(array_filter($data['concept_traits'] ?? [])), 0, 5);
        if ($request->hasFile('image')) {
            UploadedFileReplacement::persist($request->file('image'), 'images/albums', 'public', null, fn ($path) => Album::create(array_merge($data, ['image' => $path])));
        } else {
            Album::create($data);
        }

return redirect()->route('admin.albums.index')->with('success', 'Album created');
    }

    public function edit($id)
    {
        return view('admin.albums.edit', ['album' => Album::findOrFail($id), 'groups' => Group::orderBy('name')->get()]);
    }

    public function update(Request $request, $id)
    {
        $album = Album::findOrFail($id);
        $data = $this->validated($request);
        $data['vibe'] = array_slice(array_values(array_filter($data['vibe'] ?? [])), 0, 3);
        $data['concept_traits'] = array_slice(array_values(array_filter($data['concept_traits'] ?? [])), 0, 5);
        if ($request->hasFile('image')) {
            UploadedFileReplacement::persist($request->file('image'), 'images/albums', 'public', $album->image, fn ($path) => $album->update(array_merge($data, ['image' => $path])));
        } else {
            $album->update($data);
        }

return redirect()->route('admin.albums.index')->with('success', 'Album updated');
    }

    public function destroy(MediaDeletionService $mediaDeletion, $id)
    {
        $album = Album::findOrFail($id);
        $mediaDeletion->delete($album);

        return redirect()->route('admin.albums.index')->with('success', 'Album deleted');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['group_id' => 'required|exists:groups,id', 'title' => 'required|string|max:255', 'release_date' => 'nullable|date', 'concept' => 'nullable|string|max:255', 'image' => 'nullable|image|max:2048', 'vibe' => 'array', 'vibe.*' => 'nullable|string|max:255', 'concept_traits' => 'array', 'concept_traits.*' => 'nullable|string|max:255', 'description' => 'nullable|string']);
    }
}
