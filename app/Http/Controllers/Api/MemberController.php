<?php

namespace App\Http\Controllers\Api;

use App\Models\Member;
use Illuminate\Http\Request;

class MemberController extends ApiController
{
    public function index()
    {
        // return members without automatically embedding the full group object
        return $this->success(Member::all());
    }

    public function show(Member $member)
    {
        $member->load('group');
        return $this->success($member);
    }

    public function store(Request $request)
    {
        $attrs = $request->validate([
            'name' => 'required|string',
            'group_id' => 'required|exists:groups,id',
            'birth_date' => 'nullable|date',
            'role' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        $member = Member::create($attrs);
        return $this->success($member, 201);
    }
}
