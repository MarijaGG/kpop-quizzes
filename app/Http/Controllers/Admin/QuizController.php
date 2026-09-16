<?php
namespace App\Http\Controllers\Admin;
use App\Models\Group;
use App\Models\Member;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class QuizController extends BaseAdminController
{
 public function index(Request $request){$query=Quiz::with(['questions','group','member'])->latest();if($request->filled('group_id'))$query->where('group_id',$request->group_id);return view('admin.quizzes.index',['quizzes'=>$query->paginate(20)->withQueryString()]);}
 public function create(){return view('admin.quizzes.create',['groups'=>Group::orderBy('name')->get(),'members'=>Member::orderBy('name')->get()]);}
 public function store(Request $request){$data=$this->validated($request,'array|size:10');$quiz=Quiz::create($this->payload($request,$data));foreach($data['questions'] as $order=>$text)$quiz->questions()->create(['text'=>$text,'order'=>$order+1]);return redirect()->route('admin.quizzes.index')->with('success','Quiz created with 10 questions');}
 public function edit($quiz){$quiz=Quiz::with('questions')->findOrFail(is_object($quiz)?$quiz->id:$quiz);return view('admin.quizzes.edit',['quiz'=>$quiz,'groups'=>Group::orderBy('name')->get(),'members'=>Member::orderBy('name')->get(),'questions'=>$quiz->questions->sortBy('order')->values()]);}
 public function update(Request $request,$quiz){$quiz=Quiz::findOrFail(is_object($quiz)?$quiz->id:$quiz);$data=$this->validated($request,'array');$quiz->update($this->payload($request,$data,$quiz));if($request->has('questions')){$quiz->questions()->delete();foreach($data['questions'] as $order=>$text)if(is_string($text)&&trim($text)!=='')$quiz->questions()->create(['text'=>$text,'order'=>$order+1]);}return redirect()->route('admin.quizzes.index')->with('success','Quiz updated');}
 public function destroy($quiz){$quiz=Quiz::findOrFail(is_object($quiz)?$quiz->id:$quiz);Storage::disk('public')->delete($quiz->image);$quiz->delete();return redirect()->route('admin.quizzes.index')->with('success','Quiz deleted');}
 private function validated(Request $request,string $questions):array{return $request->validate(['group_id'=>'nullable|exists:groups,id','member_id'=>'nullable|exists:members,id','name'=>'required|string|max:255','image'=>'nullable|image|max:2048','questions'=>$questions,'questions.*'=>'nullable|string']);}
 private function payload(Request $request,array $data,?Quiz $quiz=null):array{$payload=['group_id'=>$data['group_id']??null,'member_id'=>$data['member_id']??null,'name'=>$data['name']];if($request->hasFile('image')){if($quiz)Storage::disk('public')->delete($quiz->image);$payload['image']=$request->file('image')->store('images/quizzes','public');}return $payload;}
}