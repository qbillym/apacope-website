<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use App\Models\{Module,SessionPlan};
class PlanController extends Controller{
private function ok($r){abort_unless($r->user()->role==='trainer',403);}
public function index(Request $r){$this->ok($r);$u=$r->user();$classes=$u->classes()->orderBy('id')->get();$mods=Module::whereIn('school_class_id',$classes->pluck('id'))->where('trainer_id',$u->id)->pluck('name')->unique()->values();
return view('plans',['classes'=>$classes,'mods'=>$mods,'plans'=>SessionPlan::with('schoolClass')->where('user_id',$u->id)->orderByDesc('date')->get()]);}
public function store(Request $r){$this->ok($r);$v=$r->validate(['school_class_id'=>'required|integer','module'=>'required|max:80','date'=>'required|date','topic'=>'required|max:150','objectives'=>'nullable|max:800']);$r->user()->classes()->findOrFail($v['school_class_id']);abort_unless(Module::where('school_class_id',$v['school_class_id'])->where('name',$v['module'])->where('trainer_id',$r->user()->id)->exists(),422,'Choose a subject assigned to you.');SessionPlan::create($v+['user_id'=>$r->user()->id]);return back()->with('ok','Session plan saved.');}
public function toggle(Request $r,SessionPlan $plan){$this->ok($r);abort_unless($plan->user_id===$r->user()->id,403);$plan->update(['delivered'=>!$plan->delivered]);return back()->with('ok',$plan->delivered?'Marked as delivered.':'Marked as not delivered.');}}
