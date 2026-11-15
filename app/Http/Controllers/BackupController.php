<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use App\Services\Backup;use App\Models\Audit;
class BackupController extends Controller{
private function ok($r){abort_unless($r->user()->role==='admin',403);}
public function index(Request $r){$this->ok($r);return view('backups',['files'=>Backup::files(),'check'=>$r->verify?Backup::verify($r->verify):null,'name'=>$r->verify]);}
public function run(Request $r){$this->ok($r);$f=Backup::run();Audit::log('Backup created',$f);return redirect('/backups')->with('ok','Backup created: '.$f);}
public function download(Request $r,$name){$this->ok($r);$f=Backup::dir().'/'.basename($name);abort_unless(is_file($f),404);return response()->download($f);}}
