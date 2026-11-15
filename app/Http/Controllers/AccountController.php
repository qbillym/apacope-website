<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use Illuminate\Support\Facades\Hash;use App\Models\{User,Student,SchoolClass,AccountRequest,Audit};
class AccountController extends Controller{
public function form(){return view('register',['classes'=>SchoolClass::orderBy('id')->get()]);}
public function store(Request $r){$r->merge(['student_name'=>$r->role==='student'?$r->name:$r->student_name,'sdms_no'=>trim((string)$r->input('sdms_no'))]);
$v=$r->validate(['role'=>'required|in:parent,student','name'=>'required|max:100','email'=>'required|email|unique:users|unique:account_requests,email','password'=>'required|min:8|confirmed','school_class_id'=>'required|exists:school_classes,id','student_name'=>'required|max:120','sdms_no'=>'nullable|string|max:50']);
$normalize=fn($x)=>preg_replace('/\s+/',' ',mb_strtolower(trim($x)));$sdmsNo=$v['sdms_no']??'';
$studentQuery=Student::where('school_class_id',$v['school_class_id']);if($sdmsNo!=='')$studentQuery->where('sdms_no',$sdmsNo);
$matches=$studentQuery->get()->filter(fn($s)=>$sdmsNo!==''?$s->sdms_no===$sdmsNo:$normalize($s->name)===$normalize($v['student_name']));
if($matches->count()!==1)return back()->withInput()->withErrors(['student_name'=>$matches->isEmpty()?'We could not verify the student in that class. Check the name or SDMS number, or ask the school.':'More than one student has that name. Enter the student’s SDMS number to identify the right account.']);
$stu=$matches->first();
if($v['role']==='parent'){if(User::where('role','parent')->where('student_id',$stu->id)->count()>=2)return back()->withInput()->withErrors(['student_name'=>'This student already has two parent accounts. Please contact the school.']);
User::create(['name'=>$v['name'],'email'=>$v['email'],'password'=>$v['password'],'role'=>'parent','student_id'=>$stu->id]);Audit::log('Parent account approved automatically',$v['email'].' · '.$stu->name);
return redirect('/login')->with('ok','Your details were verified and your account is approved. You can sign in now.');}
if(User::where('role','student')->where('student_id',$stu->id)->exists())return back()->withInput()->withErrors(['student_name'=>'This student already has an account.']);
AccountRequest::create(['role'=>'student','name'=>$v['name'],'email'=>$v['email'],'password'=>Hash::make($v['password']),'school_class_id'=>$v['school_class_id'],'student_name'=>$v['student_name'],'student_id'=>$stu->id]);
return redirect('/login')->with('ok','Request sent. Your DOS will approve it, then you can sign in.');}}
