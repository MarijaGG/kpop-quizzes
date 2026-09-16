<?php
namespace App\Http\Controllers\Admin;
use App\Models\Album;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class AlbumController extends BaseAdminController
{
 public function index(Request $request){$query=Album::with('group')->latest();if($request->filled('group_id'))$query->where('group_id',$request->group_id);return view('admin.albums.index',['albums'=>$query->paginate(20)->withQueryString()]);}
 public function create(){return view('admin.albums.create',['groups'=>Group::orderBy('name')->get()]);}
 public function store(Request $request){$data=$this->validated($request);$data['vibe']=array_slice(array_values(array_filter($data['vibe']??[])),0,3);$data['concept_traits']=array_slice(array_values(array_filter($data['concept_traits']??[])),0,5);$data=$this->image($request,$data);Album::create($data);return redirect()->route('admin.albums.index')->with('success','Album created');}
 public function edit($id){return view('admin.albums.edit',['album'=>Album::findOrFail($id),'groups'=>Group::orderBy('name')->get()]);}
 public function update(Request $request,$id){$album=Album::findOrFail($id);$data=$this->validated($request);$data['vibe']=array_slice(array_values(array_filter($data['vibe']??[])),0,3);$data['concept_traits']=array_slice(array_values(array_filter($data['concept_traits']??[])),0,5);if($request->hasFile('image')){Storage::disk('public')->delete($album->image);$data=$this->image($request,$data);}$album->update($data);return redirect()->route('admin.albums.index')->with('success','Album updated');}
 public function destroy($id){$album=Album::findOrFail($id);Storage::disk('public')->delete($album->image);$album->delete();return redirect()->route('admin.albums.index')->with('success','Album deleted');}
 private function validated(Request $request):array{return $request->validate(['group_id'=>'required|exists:groups,id','title'=>'required|string|max:255','release_date'=>'nullable|date','concept'=>'nullable|string|max:255','image'=>'nullable|image|max:2048','vibe'=>'array','vibe.*'=>'nullable|string|max:255','concept_traits'=>'array','concept_traits.*'=>'nullable|string|max:255','description'=>'nullable|string']);}
 private function image(Request $request,array $data):array{if($request->hasFile('image'))$data['image']=$request->file('image')->store('images/albums','public');return $data;}
}