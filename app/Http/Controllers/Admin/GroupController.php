<?php

namespace App\Http\Controllers\Admin;

use App\Models\Group;
use App\Services\MediaDeletionService;
use App\Support\UploadedFileReplacement;
use Illuminate\Http\Request;

class GroupController extends BaseAdminController
{
    public function index()
    {
        return view('admin.groups.index', ['groups' => Group::latest()->paginate(20)]);
    }

    public function create()
    {
        return view('admin.groups.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            UploadedFileReplacement::persist($request->file('image'), 'images/groups', 'public', null, fn ($path) => Group::create(array_merge($data, ['image' => $path])));
        } else {
            Group::create($data);
        }

return redirect()->route('admin.groups.index')->with('success', 'Group created');
    }

    public function edit($id)
    {
        return view('admin.groups.edit', ['group' => Group::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            UploadedFileReplacement::persist($request->file('image'), 'images/groups', 'public', $group->image, fn ($path) => $group->update(array_merge($data, ['image' => $path])));
        } else {
            $group->update($data);
        }

return redirect()->route('admin.groups.index')->with('success', 'Group updated');
    }

    public function destroy(MediaDeletionService $mediaDeletion, $id)
    {
        $group = Group::findOrFail($id);
        $mediaDeletion->delete($group);

        return redirect()->route('admin.groups.index')->with('success', 'Group deleted');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['name' => 'required|string|max:255', 'debut_date' => 'nullable|date', 'concept' => 'nullable|string|max:255', 'about' => 'nullable|string', 'description' => 'nullable|string', 'image' => 'nullable|image|max:2048']);
    }
}
