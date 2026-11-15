<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;use App\Services\Audience;use App\Models\{Announcement,User,SchoolClass,Audit};
class AnnouncementController extends Controller{
const ROLES=['all'=>'Everyone in the school','dosb'=>'DOS · Nursery, Primary & O-Level','dost'=>'DOS · TVET Software Development','trainer'=>'All teachers and trainers','student'=>'All students','parent'=>'All parents','accountant'=>'Accountant','storekeeper'=>'Storekeeper'];
private function allowed($u){return match($u->role){'head'=>array_keys(self::ROLES),'dosb','dost'=>['trainer'],'accountant'=>['parent'],default=>[]};}
private function classes($u){return in_array($u->role,['head','accountant'])?SchoolClass::orderBy('id')->get():$u->classes()->orderBy('id')->get();}
private function can($u){return in_array($u->role,['head','dosb','dost','trainer','accountant']);}
public function index(Request $r){$u=$r->user();$ctx=Audience::ctx();$list=Audience::visible($u);$users=User::all();$tot=[];
foreach($list as $a)if($a->user_id===$u->id&&$a->targets->isNotEmpty())$tot[$a->id]=Audience::recipients($a,$users,$ctx);
$readers=DB::table('announcement_reads')->join('users','users.id','=','announcement_reads.user_id')->whereIn('announcement_reads.announcement_id',$list->where('user_id',$u->id)->pluck('id'))->get(['announcement_reads.announcement_id as a','users.name as n'])->groupBy('a');
return view('announcements',['list'=>$list,'tot'=>$tot,'readers'=>$readers,'reads'=>DB::table('announcement_reads')->where('user_id',$u->id)->pluck('announcement_id')->all(),'can'=>$this->can($u),
'opts'=>['roles'=>collect(self::ROLES)->only($this->allowed($u)),'teachers'=>in_array($u->role,['head','dosb','dost'])?User::where('role','trainer')->orderBy('name')->get():collect(),'classes'=>$this->classes($u),'students'=>$u->role!=='accountant']]);}
public function store(Request $r){$u=$r->user();abort_unless($this->can($u),403);$v=$r->validate(['title'=>'required|max:120','body'=>'required|max:1000']);
$cls=$this->classes($u);$cid=$cls->pluck('id')->all();$names=$cls->pluck('name','id');$T=[];$L=[];
foreach(array_intersect((array)$r->roles,$this->allowed($u)) as $x){$T[]=['kind'=>$x==='all'?'all':'role','value'=>$x];$L[]=self::ROLES[$x];}
if(in_array($u->role,['head','dosb','dost'])){$ids=User::where('role','trainer')->whereIn('id',(array)$r->users)->pluck('id');foreach($ids as $i)$T[]=['kind'=>'user','value'=>(string)$i];if($ids->count())$L[]=$ids->count().' selected teacher(s)';}
if($u->role!=='accountant'){$s=array_values(array_intersect(array_map('intval',(array)$r->cs),$cid));foreach($s as $i)$T[]=['kind'=>'cs','value'=>(string)$i];if($s)$L[]='Students of '.implode(', ',array_map(fn($i)=>$names[$i],$s));}
$p=array_values(array_intersect(array_map('intval',(array)$r->cp),$cid));foreach($p as $i)$T[]=['kind'=>'cp','value'=>(string)$i];if($p)$L[]='Parents of '.implode(', ',array_map(fn($i)=>$names[$i],$p));
if(!$T)return back()->with('err','Choose at least one person or class to receive it.')->withInput();
$a=Announcement::create($v+['user_id'=>$u->id,'audience'=>mb_substr(implode('; ',$L),0,250)]);foreach($T as $t)$a->targets()->create($t);Audit::log('Announcement published',$v['title']);
return back()->with('ok','Announcement published to: '.$a->audience.'.');}
public function ack(Request $r,Announcement $announcement){abort_unless(Audience::sees($r->user(),$announcement->load('targets'),Audience::ctx()),403);DB::table('announcement_reads')->insertOrIgnore(['announcement_id'=>$announcement->id,'user_id'=>$r->user()->id]);return back();}}
