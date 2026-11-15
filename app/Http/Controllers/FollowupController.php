<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use App\Services\Absence;use App\Models\{Student,Followup,Setting,Audit,FeeMessage};
class FollowupController extends Controller{
public function index(Request $r){$u=$r->user();abort_unless(in_array($u->role,['dosb','dost','head']),403);$ids=$u->classes()->pluck('id');$th=Setting::th();$rows=Absence::rows($ids);
$log=Followup::whereIn('student_id',$rows->pluck('id'))->with('student','user')->latest()->take(30)->get();
return view('followup',['rows'=>$rows,'vis'=>Absence::visible($rows,$th),'th'=>$th,'log'=>$log]);}
public function store(Request $r){$u=$r->user();abort_unless(in_array($u->role,['dosb','dost']),403);
$d=$r->validate(['student_id'=>'required|exists:students,id','contacted'=>'required|string','method'=>'required|string','agreed'=>'required|string|max:500']);
$s=Student::findOrFail($d['student_id']);abort_unless($u->classes()->where('id',$s->school_class_id)->exists(),403);
$cov=Absence::rows([$s->school_class_id])->firstWhere('id',$s->id)->absent;
Followup::create($d+['user_id'=>$u->id,'date'=>today(),'covered'=>$cov]);FeeMessage::create(['student_id'=>$s->id,'user_id'=>$u->id,'channel'=>'Attendance','body'=>'The school followed up on the absences of '.$s->name.'. Agreed: '.$d['agreed']]);Audit::log('Follow-up saved',$s->name.' · '.$cov.' absences marked explained');
return back()->with('ok',$s->name.' was followed up. The earlier absences are marked as explained. If he is absent again, he will return to this list.');}
public function rules(Request $r){abort_unless($r->user()->role==='head',403);$v=$r->validate(['th1'=>'required|integer|min:1','th2'=>'required|integer|gte:th1','th3'=>'required|integer|gte:th2']);
foreach($v as $k=>$x)Setting::updateOrCreate(['key'=>$k],['value'=>$x]);Audit::log('Absence rules changed',implode('/',$v));return back()->with('ok','Rules saved.');}}
