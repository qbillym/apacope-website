<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use App\Models\{Student,Fee,FeeMessage,Payment,SchoolClass,Audit};
class FeeController extends Controller{
private function ok($r){abort_unless($r->user()->role==='accountant',403);}
public function index(Request $r){$this->ok($r);$q=Student::with('schoolClass','fee');
if($r->q)$q->where(fn($w)=>$w->where('name','like',"%{$r->q}%")->orWhere('admission_no','like',"%{$r->q}%")->orWhereHas('schoolClass',fn($c)=>$c->where('name','like',"%{$r->q}%")));
if($r->class)$q->where('school_class_id',(int)$r->class);
$rows=$q->get()->map(function($s){$s->bal=$s->fee?$s->fee->invoice-$s->fee->paid:0;return $s;});if($r->owe)$rows=$rows->where('bal','>',0);
$lc=fn($s)=>mb_strtolower($s->name);
$rows=match($r->sort){'name_desc'=>$rows->sortByDesc($lc),'class'=>$rows->sortBy(fn($s)=>sprintf('%03d',$s->school_class_id).$lc($s)),'bal_desc'=>$rows->sortByDesc('bal'),'bal_asc'=>$rows->sortBy('bal'),'old'=>$rows->sortBy('id'),'new'=>$rows->sortByDesc('id'),default=>$rows->sortBy($lc)};
$t=Fee::selectRaw('sum(invoice) i,sum(paid) p,sum(case when invoice>paid then 1 else 0 end) n')->first();
return view('fees',['rows'=>$rows->values(),'t'=>$t,'classes'=>SchoolClass::orderBy('id')->get(),'sent'=>FeeMessage::with('student')->where('channel','Portal')->latest()->take(10)->get()]);}
public function show(Request $r,Student $student){$this->ok($r);$student->load('schoolClass','fee');
return view('fee_student',['s'=>$student,'pays'=>Payment::where('student_id',$student->id)->latest()->get(),'msgs'=>FeeMessage::where('student_id',$student->id)->where('channel','Portal')->latest()->take(8)->get()]);}
public function pay(Request $r,Student $student){$this->ok($r);$v=$r->validate(['amount'=>'required|integer|min:1','note'=>'nullable|max:120']);$f=$student->fee;$amt=min($v['amount'],max(0,$f->invoice-$f->paid));
if($amt<=0)return back()->with('err','There is no balance to pay.');$f->increment('paid',$amt);$f->refresh();if($f->paid>=$f->invoice)$f->update(['reason'=>null,'promise_date'=>null]);Payment::create(['student_id'=>$student->id,'user_id'=>$r->user()->id,'amount'=>$amt,'note'=>$v['note']??null]);
Audit::log('Payment recorded',$student->name.' · '.number_format($amt).' RWF');return back()->with('ok','Payment of '.number_format($amt).' RWF recorded.');}
public function settle(Request $r,Student $student){$this->ok($r);$f=$student->fee;$amt=$f->invoice-$f->paid;if($amt>0){Payment::create(['student_id'=>$student->id,'user_id'=>$r->user()->id,'amount'=>$amt,'note'=>'Marked as fully paid']);$f->update(['paid'=>$f->invoice,'reason'=>null,'promise_date'=>null]);}
Audit::log('Marked fully paid',$student->name);return back()->with('ok',$student->name.' is marked as fully paid.');}
public function note(Request $r,Student $student){$this->ok($r);if($r->has('clear')){$student->fee->update(['reason'=>null,'promise_date'=>null]);return back()->with('ok','Note cleared.');}$v=$r->validate(['reason'=>'nullable|max:255','promise_date'=>'nullable|date']);$student->fee->update(['reason'=>$v['reason']??null,'promise_date'=>$v['promise_date']??null]);Audit::log('Fee note updated',$student->name);return back()->with('ok','Saved.');}
public function invoice(Request $r,Student $student){$this->ok($r);$v=$r->validate(['invoice'=>'required|integer|min:0']);$student->fee->update($v);$z=$student->fee->refresh();if($z->paid>=$z->invoice)$z->update(['reason'=>null,'promise_date'=>null]);Audit::log('Invoice changed',$student->name.' · '.$v['invoice']);return back()->with('ok','Invoice updated.');}
public function messageOne(Request $r,Student $student){$this->ok($r);$v=$r->validate(['body'=>'required|max:300']);FeeMessage::create(['student_id'=>$student->id,'user_id'=>$r->user()->id,'channel'=>'Portal','body'=>$v['body']]);return back()->with('ok','Message sent to the parent.');}
public function bulk(Request $r){$this->ok($r);$v=$r->validate(['body'=>'required|max:300','ids'=>'required']);$ids=collect(explode(',',$v['ids']))->map(fn($x)=>(int)$x)->filter()->unique();
foreach($ids as $id)FeeMessage::create(['student_id'=>$id,'user_id'=>$r->user()->id,'channel'=>'Portal','body'=>$v['body']]);return back()->with('ok','Message sent to '.$ids->count().' parent(s).');}}
