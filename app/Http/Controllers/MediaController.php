<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;use Illuminate\Validation\Rule;use App\Models\{MediaItem,SchoolClass,Audit};
class MediaController extends Controller{
const CATS=['Documentaries and technology videos','Career guidance and entrepreneurship','Technology news and innovation','Music, arts, sports and entertainment','School events and student projects','Quizzes, puzzles and coding challenges'];
private function ids($itemIds){return DB::table('media_classes')->whereIn('media_item_id',$itemIds)->get()->groupBy('media_item_id')->map(fn($g)=>$g->pluck('school_class_id'));}
private function sees($u,$m,$ids,$my){if($u->role==='head'||$m->user_id===$u->id)return true;$t=$ids[$m->id]??collect();if($m->school_wide||$t->isEmpty())return true;return $t->intersect($my)->isNotEmpty();}
private function canMod($u,$m,$ids){if($u->role==='head')return true;if(!in_array($u->role,['dosb','dost']))return false;return ($ids[$m->id]??collect())->intersect($u->classes()->pluck('id'))->isNotEmpty();}
public function index(Request $r){$u=$r->user();$my=$u->classes()->pluck('id');$all=MediaItem::with('user')->latest()->get();$ids=$this->ids($all->pluck('id'));
$list=$all->where('status','approved')->filter(fn($m)=>$this->sees($u,$m,$ids,$my))->when($r->cat,fn($c)=>$c->where('category',$r->cat))->values();
$pending=$all->where('status','pending')->filter(fn($m)=>$this->canMod($u,$m,$ids))->values();
return view('media',['cats'=>self::CATS,'list'=>$list,'pending'=>$pending,'ids'=>$ids,'cn'=>SchoolClass::pluck('name','id'),'mine'=>$all->where('user_id',$u->id)->where('status','pending')->count(),'mod'=>in_array($u->role,['head','dosb','dost']),'head'=>$u->role==='head',
'submit'=>in_array($u->role,['student','trainer','head','dosb','dost']),'classes'=>$u->role==='head'?SchoolClass::orderBy('id')->get():($u->role==='student'?collect():$u->classes()->orderBy('id')->get()),'role'=>$u->role]);}
public function store(Request $r){$u=$r->user();abort_unless(in_array($u->role,['student','trainer','head','dosb','dost']),403);
$v=$r->validate(['category'=>['required',Rule::in(self::CATS)],'title'=>'required|max:150','description'=>'nullable|max:500','link'=>'nullable|url','file'=>'nullable|file|max:102400|mimes:jpg,jpeg,png,pdf,mp4,webm']);unset($v['file']);
if($r->hasFile('file'))$v['path']=$r->file('file')->store('media','local');if(empty($v['link'])&&empty($v['path']))return back()->with('err','Add a link or choose a file.')->withInput();
$wide=false;$mine=$u->classes()->pluck('id')->all();
if($u->role==='student')$cids=$mine;
elseif($u->role==='head'){$wide=$r->boolean('wide');$cids=array_values(array_intersect(array_map('intval',(array)$r->classes),SchoolClass::pluck('id')->all()));}
else{$cids=array_values(array_intersect(array_map('intval',(array)$r->classes),$mine));if($u->role==='trainer'&&!$cids)$cids=$mine;}
if(!$wide&&!$cids)return back()->with('err','Choose at least one class that this is for.')->withInput();
$auto=$u->role!=='student';$m=MediaItem::create($v+['user_id'=>$u->id,'status'=>$auto?'approved':'pending','school_wide'=>$wide]);foreach($cids as $c)DB::table('media_classes')->insert(['media_item_id'=>$m->id,'school_class_id'=>$c]);
return back()->with('ok',$auto?'Published for the chosen classes.':'Sent for approval by your DOS. It will appear for your class once approved.');}
public function decide(Request $r,MediaItem $item,$act){$u=$r->user();abort_unless(in_array($act,['approve','reject'])&&$this->canMod($u,$item,$this->ids([$item->id])),403);$item->update(['status'=>$act==='approve'?'approved':'rejected']);Audit::log('Media '.$act.'d',$item->title);return back()->with('ok',$act==='approve'?'Approved and published.':'Rejected.');}
public function open(Request $r,MediaItem $item){$u=$r->user();$ids=$this->ids([$item->id]);abort_unless(($item->status==='approved'&&$this->sees($u,$item,$ids,$u->classes()->pluck('id')))||$this->canMod($u,$item,$ids)||$item->user_id===$u->id,403);return redirect($item->link?:url('/files/media/'.$item->id));}
public function destroy(Request $r,MediaItem $item){$u=$r->user();abort_unless($item->user_id===$u->id||$this->canMod($u,$item,$this->ids([$item->id])),403);DB::table('media_classes')->where('media_item_id',$item->id)->delete();$item->delete();return back()->with('ok','Removed.');}
public function allowed($u,$m){$ids=$this->ids([$m->id]);return ($m->status==='approved'&&$this->sees($u,$m,$ids,$u->classes()->pluck('id')))||$this->canMod($u,$m,$ids)||$m->user_id===$u->id;}}
