<?php

namespace App\Http\Controllers\Api;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends ApiController
{
    public function index()
    {
        $groups = Group::with(['members','albums','quizzes'])->get();

        // remove nested `group` relation from each member to avoid repeating the parent group
        foreach ($groups as $group) {
            foreach ($group->members as $member) {
                $member->setRelation('group', null);
            }
        }

        return $this->success($groups);
    }

    public function show(Group $group)
    {
        $group->load(['members','albums','quizzes']);

        foreach ($group->members as $member) {
            $member->setRelation('group', null);
        }

        return $this->success($group);
    }

    public function store(Request $request)
    {
        $attrs = $request->validate([
            'name' => 'required|string',
            'debut_date' => 'nullable|date',
            'concept' => 'nullable|string',
            'about' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        $group = Group::create($attrs);
        return $this->success($group, 201);
    }
}
