<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\BaseAdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;

class MemberController extends BaseAdminController
{
    public function index()
    {
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $all = $json['members'] ?? [];

        $page = (int) request('page', 1);
        $perPage = 20;
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $items = array_map(function($i){ return (object)$i; }, $items);
        $members = new LengthAwarePaginator($items, $total, $perPage, $page, ['path' => url()->current()]);
        return view('admin.members.index', compact('members'));
    }

    public function create()
    {
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $groups = $json['groups'] ?? [];
        $groups = array_map(function($i){ return (object)$i; }, $groups);
        return view('admin.members.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id' => 'required',
            'name' => 'required|string|max:255',
            'about' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'traits' => 'array',
            'traits.*' => 'nullable|string|max:255',
        ]);

        // Ensure traits array is limited to 5
        $data['traits'] = array_values(array_filter($data['traits'] ?? []));
        $data['traits'] = array_slice($data['traits'], 0, 5);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('images/members', 'public');
        }

        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $items = $json['members'] ?? [];
        $ids = array_column($items, 'id');
        $max = count($ids) ? max($ids) : 0;
        $data['id'] = $data['id'] ?? ($max + 1);
        $items[] = $data;
        $json['members'] = $items;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));

        return redirect()->route('admin.members.index')->with('success', 'Member created');
    }

    public function edit($id)
    {
        if (is_object($id) && isset($id->id)) {
            $id = $id->id;
        }
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $groups = $json['groups'] ?? [];
        $groups = array_map(function($i){ return (object)$i; }, $groups);
        $member = null;
        foreach ($json['members'] ?? [] as $item) {
            if ((string)($item['id'] ?? '') === (string)$id) { $member = $item; break; }
        }
        return view('admin.members.edit', ['member' => (object)($member ?? []), 'groups' => $groups]);
    }

    public function update(Request $request, $id)
    {
        if (is_object($id) && isset($id->id)) {
            $id = $id->id;
        }
        $data = $request->validate([
            'group_id' => 'required',
            'name' => 'required|string|max:255',
            'about' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'traits' => 'array',
            'traits.*' => 'nullable|string|max:255',
        ]);

        $data['traits'] = array_values(array_filter($data['traits'] ?? []));
        $data['traits'] = array_slice($data['traits'], 0, 5);

        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $existing = [];
        foreach ($json['members'] ?? [] as $item) {
            if ((string)($item['id'] ?? '') === (string)$id) { $existing = $item; break; }
        }

        if ($request->hasFile('image')) {
            if (! empty($existing['image'])) {
                Storage::disk('public')->delete($existing['image']);
            }
            $data['image'] = $request->file('image')->store('images/members', 'public');
        }

        $updated = [];
        foreach ($json['members'] ?? [] as $item) {
            if ((string)($item['id'] ?? '') === (string)$id) {
                $item = array_merge($item, $data);
            }
            $updated[] = $item;
        }
        $json['members'] = $updated;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));

        return redirect()->route('admin.members.index')->with('success', 'Member updated');
    }

    public function destroy($id)
    {
        if (is_object($id) && isset($id->id)) {
            $id = $id->id;
        }
        $json = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $existing = [];
        $new = [];
        foreach ($json['members'] ?? [] as $item) {
            if ((string)($item['id'] ?? '') === (string)$id) { $existing = $item; continue; }
            $new[] = $item;
        }
        if (! empty($existing['image'])) {
            Storage::disk('public')->delete($existing['image']);
        }
        $json['members'] = $new;
        file_put_contents(resource_path('data/api.json'), json_encode($json, JSON_PRETTY_PRINT));
        return redirect()->route('admin.members.index')->with('success', 'Member deleted');
    }
}
