<?php
namespace App\Http\Controllers\Admin;
use App\Models\Album;
use App\Models\Group;
use App\Models\Member;
use App\Models\Quiz;
use Illuminate\Http\Request;
class QuestionController extends BaseAdminController
{
 public function index($quizId){$quiz=Quiz::findOrFail($quizId);return view('admin.questions.index',['questions'=>$quiz->questions()->orderBy('order')->paginate(50),'quiz'=>$quiz]);}
 public function create($quizId){$quiz=Quiz::findOrFail($quizId);$question=$quiz->questions()->create(['text'=>'','order'=>($quiz->questions()->max('order')??0)+1]);return redirect()->route('admin.quizzes.questions.edit',[$quizId,$question->id]);}
 public function edit($quizId,$questionId){$quiz=Quiz::findOrFail($quizId);$question=$quiz->questions()->findOrFail($questionId);$answers=$question->answers()->get()->map(fn($answer)=>(object)array_merge($answer->toArray(),$answer->meta??[]));return view('admin.questions.edit',['quiz_id'=>$quizId,'question'=>$question,'answers'=>$answers,'members'=>Member::when($quiz->group_id,fn($q)=>$q->where('group_id',$quiz->group_id))->orderBy('name')->get(),'groups'=>Group::orderBy('name')->get(),'albums'=>Album::when($quiz->group_id,fn($q)=>$q->where('group_id',$quiz->group_id))->orderBy('title')->get(),'question_target_type'=>$answers->firstWhere('target_type','!=',null)?->target_type??'member']);}
 public function update(Request $request,$quizId,$questionId){$quiz=Quiz::findOrFail($quizId);$question=$quiz->questions()->findOrFail($questionId);$data=$request->validate(['answers'=>'array|max:8','answers.*.text'=>'nullable|string|max:1000','target_type'=>'nullable|in:group,member,album','answers.*.target_id'=>'nullable|integer']);$question->answers()->delete();foreach($data['answers']??[] as $order=>$answer)if(!empty($answer['text']))$question->answers()->create(['text'=>$answer['text'],'points'=>0,'meta'=>['order'=>$order+1,'target_type'=>$data['target_type']??null,'target_id'=>($answer['target_id']??'')!==''?(int)$answer['target_id']:null]]);return redirect()->route('admin.quizzes.questions.index',$quizId)->with('success','Answers saved');}
}