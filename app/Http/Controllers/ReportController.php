<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use App\Models\{Student,Attendance};
class ReportController extends Controller{
private function ok($u){abort_unless(in_array($u->role,['head','dosb','dost','accountant']),403);}
public function index(Request $r){$this->ok($r->user());return view('reports',['kinds'=>$r->user()->role==='accountant'?['fees'=>'Fee balances (Excel / CSV)']:['attendance'=>'Attendance, last 30 days (Excel / CSV)','students'=>'Student list (Excel / CSV)']]);}
public function csv(Request $r,$kind){$u=$r->user();$this->ok($u);$ids=$u->classes()->pluck('id');$acc=$u->role==='accountant';$h=[];$rows=[];
if($kind==='fees'&&$acc){$h=['Student','Class','Invoice','Paid','Balance'];foreach(Student::with('schoolClass','fee')->get() as $s)$rows[]=[$s->name,$s->schoolClass->name,$s->fee->invoice,$s->fee->paid,$s->fee->invoice-$s->fee->paid];}
elseif($kind==='students'&&!$acc){$h=['Admission','Name','Class','Guardian phone'];foreach(Student::with('schoolClass')->whereIn('school_class_id',$ids)->get() as $s)$rows[]=[$s->admission_no,$s->name,$s->schoolClass->name,$s->guardian_phone];}
elseif($kind==='attendance'&&!$acc){$h=['Date','Class','Student','Status'];foreach(Attendance::with('student.schoolClass')->whereIn('school_class_id',$ids)->where('date','>=',today()->subDays(30))->orderBy('date')->get() as $a)$rows[]=[$a->date->toDateString(),$a->student->schoolClass->name,$a->student->name,$a->status];}
else abort(404);
return response()->streamDownload(function()use($h,$rows){$o=fopen('php://output','w');fputcsv($o,$h);foreach($rows as $x)fputcsv($o,$x);fclose($o);},"$kind.csv",['Content-Type'=>'text/csv']);}}
