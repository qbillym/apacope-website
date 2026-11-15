<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use App\Models\{User,AccountRequest,Audit};
class RequestController extends Controller{
private function ok($r){abort_unless(in_array($r->user()->role,['dosb','dost']),403);}
public function index(Request $r){$this->ok($r);return view('requests',['list'=>AccountRequest::with('schoolClass')->whereIn('school_class_id',$r->user()->classes()->pluck('id'))->where('status','pending')->latest()->get()]);}
public function approve(Request $r,AccountRequest $accountRequest){$this->ok($r);abort_unless($r->user()->classes()->where('id',$accountRequest->school_class_id)->exists(),403);
if(User::where('email',$accountRequest->email)->doesntExist())User::create(['name'=>$accountRequest->name,'email'=>$accountRequest->email,'password'=>$accountRequest->password,'role'=>'student','student_id'=>$accountRequest->student_id]);
$accountRequest->update(['status'=>'approved']);Audit::log('Student account approved',$accountRequest->email);return back()->with('ok','Approved. The student can sign in now.');}
public function reject(Request $r,AccountRequest $accountRequest){$this->ok($r);abort_unless($r->user()->classes()->where('id',$accountRequest->school_class_id)->exists(),403);$accountRequest->update(['status'=>'rejected']);Audit::log('Student account rejected',$accountRequest->email);return back()->with('ok','Request rejected.');}}
