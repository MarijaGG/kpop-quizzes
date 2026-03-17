<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\BaseAdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;

class AlbumController extends BaseAdminController
{
    public function index()
    {
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $all = $json['albums'] ?? [];

        $page = (int) request('page', 1);
        $perPage = 20;
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $items = array_map(function($i){ return (object)$i; }, $items);
        $albums = new LengthAwarePaginator($items, $total, $perPage, $page, ['path' => url()->current()]);
        return view('admin.albums.index', compact('albums'));
    }

    public function create()
    {
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $groups = $json['groups'] ?? [];
        $groups = array_map(function($i){ return (object)$i; }, $groups);
        return view('admin.albums.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id' => 'required',
            'title' => 'required|string|max:255',
            'release_date' => 'nullable|date',
            'concept' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
            'vibe' => 'array',
            'vibe.*' => 'nullable|string|max:255',
            'concept_traits' => 'array',
            'concept_traits.*' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $data['vibe'] = array_values(array_filter($data['vibe'] ?? []));
        $data['vibe'] = array_slice($data['vibe'], 0, 3);
        $data['concept_traits'] = array_values(array_filter($data['concept_traits'] ?? []));
        $data['concept_traits'] = array_slice($data['concept_traits'], 0, 5);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('images/albums', 'public');
        }

        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $items = $json['albums'] ?? [];
        $ids = array_column($items, 'id');
        $max = count($ids) ? max($ids) : 0;
        $data['id'] = $data['id'] ?? ($max + 1);
        $items[] = $data;
        $json['albums'] = $items;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));

        return redirect()->route('admin.albums.index')->with('success', 'Album created');
    }

    public function edit($id)
    {
        if (is_object($id) && isset($id->id)) {
            $id = $id->id;
        }
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $groups = $json['groups'] ?? [];
        $groups = array_map(function($i){ return (object)$i; }, $groups);
        $album = null;
        foreach ($json['albums'] ?? [] as $item) {
            if ((string)($item['id'] ?? '') === (string)$id) { $album = $item; break; }
        }
        return view('admin.albums.edit', ['album' => (object)($album ?? []), 'groups' => $groups]);
    }

    public function update(Request $request, $id)
    {
        if (is_object($id) && isset($id->id)) {
            $id = $id->id;
        }
        $data = $request->validate([
            'group_id' => 'required',
            'title' => 'required|string|max:255',
            'release_date' => 'nullable|date',
            'concept' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
            'vibe' => 'array',
            'vibe.*' => 'nullable|string|max:255',
            'concept_traits' => 'array',
            'concept_traits.*' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $data['vibe'] = array_values(array_filter($data['vibe'] ?? []));
        $data['vibe'] = array_slice($data['vibe'], 0, 3);
        $data['concept_traits'] = array_values(array_filter($data['concept_traits'] ?? []));
        $data['concept_traits'] = array_slice($data['concept_traits'], 0, 5);

        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $existing = [];
        foreach ($json['albums'] ?? [] as $item) { if ((string)($item['id'] ?? '') === (string)$id) { $existing = $item; break; } }

        if ($request->hasFile('image')) {
            if (! empty($existing['image'])) {
                Storage::disk('public')->delete($existing['image']);
            }
            $data['image'] = $request->file('image')->store('images/albums', 'public');
        }

        $updated = [];
        foreach ($json['albums'] ?? [] as $item) {
            if ((string)($item['id'] ?? '') === (string)$id) { $item = array_merge($item, $data); }
            $updated[] = $item;
        }
        $json['albums'] = $updated;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));

        return redirect()->route('admin.albums.index')->with('success', 'Album updated');
    }

    public function destroy($id)
    {
        if (is_object($id) && isset($id->id)) {
            $id = $id->id;
        }
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $existing = [];
        $new = [];
        foreach ($json['albums'] ?? [] as $item) {
            if ((string)($item['id'] ?? '') === (string)$id) { $existing = $item; continue; }
            $new[] = $item;
        }
        if (! empty($existing['image'])) { Storage::disk('public')->delete($existing['image']); }
        $json['albums'] = $new;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));
        return redirect()->route('admin.albums.index')->with('success', 'Album deleted');
    }
}
