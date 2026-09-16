<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Album;use App\Models\Answer;use App\Models\Group;use App\Models\Member;use App\Models\Question;use App\Models\Quiz;
use Illuminate\Http\Request;
class StaticApiController extends Controller
{
 private const MODELS=['groups'=>Group::class,'members'=>Member::class,'albums'=>Album::class,'quizzes'=>Quiz::class,'questions'=>Question::class,'answers'=>Answer::class];
 public function __construct(){ $this->middleware('auth')->only(['store','update','destroy']); }
 public function list(Request $request,$resource=null){if($resource===null)return response()->json($this->all());$model=$this->model($resource);return response()->json($model::query()->get()->map(fn($item)=>$this->format($resource,$item)));}
 public function show($resource,$id){$model=$this->model($resource);$item=$model::find($id);return $item?response()->json($this->format($resource,$item)):response()->json(null,404);}
 public function store(Request $request,$resource){$item=($this->model($resource))::create($this->attrs($resource,$request->all()));return response()->json($this->format($resource,$item),201);}
 public function update(Request $request,$resource,$id){$model=$this->model($resource);$item=$model::find($id);if(!$item)return response()->json(null,404);$item->update($this->attrs($resource,$request->all()));return response()->json($this->format($resource,$item->fresh()));}
 public function destroy($resource,$id){$model=$this->model($resource);$item=$model::find($id);if(!$item)return response()->json(null,404);$item->delete();return response()->json(null,204);}
 private function model(string $resource):string{abort_unless(isset(self::MODELS[$resource]),404);return self::MODELS[$resource];}
 private function all():array{$out=[];foreach(self::MODELS as $name=>$model)$out[$name]=$model::all()->map(fn($item)=>$this->format($name,$item))->values()->all();return $out;}
 private function attrs(string $resource,array $data):array{unset($data['id'],$data['created_at'],$data['updated_at']);if($resource==='answers'){ $meta=$data['meta']??[];foreach(['target_type','target_id'] as $key)if(array_key_exists($key,$data)){$meta[$key]=$data[$key];unset($data[$key]);}$data['meta']=$meta?:null;}return $data;}
 private function format(string $resource,object $item):array{$data=$item->toArray();if($resource==='answers'){$meta=$data['meta']??[];$data['target_type']=$meta['target_type']??null;$data['target_id']=$meta['target_id']??null;}return $data;}
}