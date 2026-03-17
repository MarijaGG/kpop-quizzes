<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\BaseAdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;

use function resource_path;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_encode;

class GroupController extends BaseAdminController
{
    public function index()
    {
        $data = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $all = $data['groups'] ?? [];

        $page = (int) request('page', 1);
        $perPage = 20;
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $items = array_map(function($i){ return (object)$i; }, $items);
        $groups = new LengthAwarePaginator($items, $total, $perPage, $page, ['path' => url()->current()]);

        return view('admin.groups.index', compact('groups'));
    }

    public function create()
    {
        return view('admin.groups.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'debut_date' => 'nullable|date',
            'concept' => 'nullable|string|max:255',
            'about' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);


        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('images/groups', 'public');
        }

        // append to static data file
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $items = $json['groups'] ?? [];
        $ids = array_column($items, 'id');
        $max = count($ids) ? max($ids) : 0;
        $data['id'] = $data['id'] ?? ($max + 1);
        $items[] = $data;
        $json['groups'] = $items;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));

        return redirect()->route('admin.groups.index')->with('success', 'Group created');
    }

    public function edit($id)
    {
        // accept either model or id from route binding
        if (is_object($id) && isset($id->id)) {
            $id = $id->id;
        }

        // retrieve fresh from static data
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $g = null;
        foreach ($json['groups'] ?? [] as $item) {
            if ((string)($item['id'] ?? '') === (string)$id) { $g = $item; break; }
        }
        return view('admin.groups.edit', ['group' => (object)$g]);
    }

    public function update(Request $request, $id)
    {
        if (is_object($id) && isset($id->id)) {
            $id = $id->id;
        }
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'debut_date' => 'nullable|date',
            'concept' => 'nullable|string|max:255',
            'about' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $existing = [];
        foreach ($json['groups'] ?? [] as $item) {
            if ((string)($item['id'] ?? '') === (string)$id) { $existing = $item; break; }
        }

        if ($request->hasFile('image')) {
            if (! empty($existing['image'])) {
                Storage::disk('public')->delete($existing['image']);
            }
            $data['image'] = $request->file('image')->store('images/groups', 'public');
        }

        // update static file
        $updated = [];
        foreach ($json['groups'] ?? [] as $item) {
            if ((string)($item['id'] ?? '') === (string)$id) {
                $item = array_merge($item, $data);
            }
            $updated[] = $item;
        }
        $json['groups'] = $updated;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));

        return redirect()->route('admin.groups.index')->with('success', 'Group updated');
    }

    public function destroy($id)
    {
        if (is_object($id) && isset($id->id)) {
            $id = $id->id;
        }
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $existing = [];
        $new = [];
        foreach ($json['groups'] ?? [] as $item) {
            if ((string)($item['id'] ?? '') === (string)$id) {
                $existing = $item;
                continue;
            }
            $new[] = $item;
        }
        if (! empty($existing['image'])) {
            Storage::disk('public')->delete($existing['image']);
        }
        $json['groups'] = $new;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));
        return redirect()->route('admin.groups.index')->with('success', 'Group deleted');
    }
}
