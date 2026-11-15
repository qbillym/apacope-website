<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use App\Models\{Payment,Student,User};
class ReceiptController extends Controller{
public function show(Request $r,Payment $payment){$u=$r->user();abort_unless($u->role==='accountant'||(in_array($u->role,['student','parent'])&&$u->student_id==$payment->student_id),403);
return view('receipt',['p'=>$payment,'s'=>Student::with('schoolClass')->find($payment->student_id),'by'=>User::find($payment->user_id)]);}}
