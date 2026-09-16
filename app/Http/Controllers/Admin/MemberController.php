<?php
namespace App\Http\Controllers\Admin;
use App\Models\Group;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class MemberController extends BaseAdminController
{
 public function index(Request $request){$query=Member::with('group')->latest();if($request->filled('group_id'))$query->where('group_id',$request->group_id);return view('admin.members.index',['members'=>$query->paginate(20)->withQueryString()]);}
 public function create(){return view('admin.members.create',['groups'=>Group::orderBy('name')->get()]);}
 public function store(Request $request){$data=$this->validated($request);$data['traits']=array_slice(array_values(array_filter($data['traits']??[])),0,5);$data=$this->image($request,$data);Member::create($data);return redirect()->route('admin.members.index')->with('success','Member created');}
 public function edit($id){return view('admin.members.edit',['member'=>Member::findOrFail($id),'groups'=>Group::orderBy('name')->get()]);}
 public function update(Request $request,$id){$member=Member::findOrFail($id);$data=$this->validated($request);$data['traits']=array_slice(array_values(array_filter($data['traits']??[])),0,5);if($request->hasFile('image')){Storage::disk('public')->delete($member->image);$data=$this->image($request,$data);}$member->update($data);return redirect()->route('admin.members.index')->with('success','Member updated');}
 public function destroy($id){$member=Member::findOrFail($id);Storage::disk('public')->delete($member->image);$member->delete();return redirect()->route('admin.members.index')->with('success','Member deleted');}
 private function validated(Request $request):array{return $request->validate(['group_id'=>'required|exists:groups,id','name'=>'required|string|max:255','about'=>'nullable|string','description'=>'nullable|string','image'=>'nullable|image|max:2048','traits'=>'array','traits.*'=>'nullable|string|max:255']);}
 private function image(Request $request,array $data):array{if($request->hasFile('image'))$data['image']=$request->file('image')->store('images/members','public');return $data;}
}