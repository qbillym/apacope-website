<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use App\Models\{User,StaffAttendance,Audit};
class StaffAttendanceController extends Controller{
private function staff($u){if($u->role==='head')return User::whereIn('role',['dosb','dost','trainer','accountant','storekeeper'])->orderBy('role')->orderBy('name')->get();
return User::where('role','trainer')->whereIn('id',$u->classes()->pluck('trainer_id')->filter())->orderBy('name')->get();}
public function index(Request $r){$u=$r->user();abort_unless(in_array($u->role,['head','dosb','dost']),403);$date=$r->date?:today()->toDateString();$staff=$this->staff($u);
$recs=StaffAttendance::where('date',$date)->whereIn('user_id',$staff->pluck('id'))->get()->keyBy('user_id');$hist=StaffAttendance::whereIn('user_id',$staff->pluck('id'))->where('date','>=',today()->subDays(29))->get()->groupBy('user_id');
$sum=$staff->map(function($s)use($hist){$g=$hist[$s->id]??collect();$n=$g->count();$p=$g->whereIn('status',['P','L'])->count();return['s'=>$s,'n'=>$n,'a'=>$g->where('status','A')->count(),'pct'=>$n?round($p/$n*100):null];});
return view('staff_attendance',['date'=>$date,'staff'=>$staff,'recs'=>$recs,'names'=>User::pluck('name','id'),'sum'=>$sum,'head'=>$u->role==='head']);}
public function store(Request $r){$u=$r->user();abort_unless(in_array($u->role,['head','dosb','dost']),403);$r->validate(['date'=>'required|date|before_or_equal:today','s'=>'required|array']);$ok=$this->staff($u)->pluck('id')->all();$n=0;
foreach($r->s as $id=>$st){if(!in_array((int)$id,$ok)||!in_array($st,['P','A','L','E']))continue;if(StaffAttendance::where(['user_id'=>$id,'date'=>$r->date])->exists())continue;StaffAttendance::create(['user_id'=>$id,'date'=>$r->date,'status'=>$st,'recorded_by'=>$u->id]);$n++;}
Audit::log('Staff attendance saved',$r->date.' · '.$n.' marked');return redirect('/staff-attendance?date='.$r->date)->with('ok',$n.' staff member(s) saved. Saved marks are locked.');}
public function reopen(Request $r,User $user){abort_unless($r->user()->role==='head',403);StaffAttendance::where(['user_id'=>$user->id,'date'=>$r->date])->delete();Audit::log('Staff attendance reopened',$user->name.' · '.$r->date);return back()->with('ok','Reopened for '.$user->name.'.');}}
