<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use Illuminate\Support\Facades\Auth;use App\Models\{Student,SchoolClass,User,Announcement,Event,SiteItem,Setting,WebsiteContent};
class AuthController extends Controller{
public function welcome(){$welcome=WebsiteContent::values();return view('home',['news'=>SiteItem::where('type','notice')->latest()->take(6)->get(),'events'=>SiteItem::where('type','event')->whereDate('date','>=',today())->orderBy('date')->take(6)->get(),'site'=>['phone'=>Setting::val('site_phone','+250 788 000 000'),'email'=>Setting::val('site_email','info@apacope.rw'),'address'=>Setting::val('site_address','Groupe Scolaire APACOPE, Nyarugenge, Kigali, Rwanda')],'welcome'=>$welcome]+$this->schoolStatistics());}
public function statistics(){return response()->json($this->schoolStatistics())->header('Cache-Control','no-store, private');}
private function schoolStatistics(){return ['n'=>Student::where('status','Enrolled')->count(),'c'=>SchoolClass::count(),'s'=>User::whereNotIn('role',['student','parent'])->where('active',true)->count()];}
public function form(){return auth()->check()?redirect('/home'):view('login');}
public function login(Request $r){$c=$r->validate(['email'=>'required|email','password'=>'required']);
if(Auth::attempt($c+["active"=>1])){$r->session()->regenerate();return redirect('/home')->with('splash',1);}
return back()->withErrors(['email'=>'Wrong email or password.'])->onlyInput('email');}
public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/');}}
