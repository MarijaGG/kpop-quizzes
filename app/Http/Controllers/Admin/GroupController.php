<?php
namespace App\Http\Controllers\Admin;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class GroupController extends BaseAdminController
{
 public function index(){return view('admin.groups.index',['groups'=>Group::latest()->paginate(20)]);}
 public function create(){return view('admin.groups.create');}
 public function store(Request $request){$data=$this->validated($request);$data=$this->image($request,$data);Group::create($data);return redirect()->route('admin.groups.index')->with('success','Group created');}
 public function edit($id){return view('admin.groups.edit',['group'=>Group::findOrFail($id)]);}
 public function update(Request $request,$id){$group=Group::findOrFail($id);$data=$this->validated($request);if($request->hasFile('image')){Storage::disk('public')->delete($group->image);$data=$this->image($request,$data);}$group->update($data);return redirect()->route('admin.groups.index')->with('success','Group updated');}
 public function destroy($id){$group=Group::findOrFail($id);Storage::disk('public')->delete($group->image);$group->delete();return redirect()->route('admin.groups.index')->with('success','Group deleted');}
 private function validated(Request $request):array{return $request->validate(['name'=>'required|string|max:255','debut_date'=>'nullable|date','concept'=>'nullable|string|max:255','about'=>'nullable|string','description'=>'nullable|string','image'=>'nullable|image|max:2048']);}
 private function image(Request $request,array $data):array{if($request->hasFile('image')){$data['image']=$request->file('image')->store('images/groups','public');}$data['image']=$data['image']??null;return $data;}
}