<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use App\Models\Event;
class EventController extends Controller{
private function opts($u){$o=$u->classes()->pluck('name')->all();return $u->role==='head'?array_merge(['Whole school'],$o):$o;}
public function index(Request $r){$u=$r->user();$n=$u->classes()->pluck('name');
$list=Event::orderBy('date')->get()->filter(fn($e)=>$e->audience==='Whole school'||$n->contains($e->audience));
return view('events',['list'=>$list,'can'=>in_array($u->role,['head','dosb','dost','trainer']),'opts'=>$this->opts($u)]);}
public function store(Request $r){$u=$r->user();abort_unless(in_array($u->role,['head','dosb','dost','trainer']),403);
$v=$r->validate(['date'=>'required|date','title'=>'required|max:150','kind'=>'required','audience'=>'required']);abort_unless(in_array($v['audience'],$this->opts($u)),403);
Event::create($v+['user_id'=>$u->id]);return back()->with('ok','Added to the calendar.');}}
