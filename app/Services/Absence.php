<?php
namespace App\Services;
use App\Models\{Student,Attendance,Followup};
class Absence{
public static function rows($ids){$fu=Followup::selectRaw('student_id, max(date) as d')->groupBy('student_id')->pluck('d','student_id');
$m=fn($q)=>$q->whereMonth('date',now()->month)->whereYear('date',now()->year);
$a=Attendance::whereIn('school_class_id',$ids)->where('status','A')->where($m)->get(['student_id','date'])->groupBy('student_id');
$e=Attendance::whereIn('school_class_id',$ids)->where('status','E')->where($m)->selectRaw('student_id, count(*) as n')->groupBy('student_id')->pluck('n','student_id');
return Student::whereIn('school_class_id',$ids)->with('schoolClass')->get()->map(function($s)use($fu,$a,$e){$l=isset($fu[$s->id])?substr((string)$fu[$s->id],0,10):null;$x=$a[$s->id]??collect();
$s->last=$l;$s->absent=$l?$x->filter(fn($r)=>$r->date->toDateString()>$l)->count():$x->count();$s->again=$l&&$s->absent>0;$s->excused=$e[$s->id]??0;return $s;});}
public static function visible($rows,$th){return $rows->filter(fn($s)=>$s->again||(!$s->last&&$s->absent>=$th[0]))->sortByDesc(fn($s)=>($s->again?1000:0)+$s->absent)->values();}}
