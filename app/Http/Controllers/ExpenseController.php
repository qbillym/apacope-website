<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use App\Models\{Expense,Fee,Audit};
class ExpenseController extends Controller{
private function ok($r){abort_unless($r->user()->role==='accountant',403);}
public function index(Request $r){$this->ok($r);return view('expenses',['rows'=>Expense::orderByDesc('date')->orderByDesc('id')->get(),'in'=>(int)Fee::sum('paid')]);}
public function store(Request $r){$this->ok($r);$v=$r->validate(['date'=>'required|date','item'=>'required|max:150','amount'=>'required|integer|min:1','note'=>'nullable|max:200']);Expense::create($v+['user_id'=>$r->user()->id]);Audit::log('Expense recorded',$v['item'].' · '.number_format($v['amount']));return back()->with('ok','Expense recorded.');}
public function destroy(Request $r,Expense $expense){$this->ok($r);Audit::log('Expense deleted',$expense->item);$expense->delete();return back()->with('ok','Deleted.');}}
