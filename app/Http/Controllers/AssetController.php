<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use App\Models\{Asset,Audit,AssetLog};
class AssetController extends Controller{
private function ok($r){abort_unless($r->user()->role==='storekeeper',403);}
public function index(Request $r){$this->ok($r);return view('inventory',['rows'=>Asset::orderBy('name')->get(),'logs'=>AssetLog::with('user')->latest()->get()->groupBy('asset_id')]);}
public function store(Request $r){$this->ok($r);$v=$r->validate(['name'=>'required|max:100','kind'=>'required|max:60','location'=>'required|max:60']);Asset::create($v);Audit::log('Asset added',$v['name']);return back()->with('ok','Asset added.');}
public function update(Request $r,Asset $asset){$this->ok($r);$v=$r->validate(['status'=>'required|in:In store,Borrowed,Damaged,In maintenance','holder'=>'nullable|max:80']);
$asset->update(['status'=>$v['status'],'holder'=>$v['status']==='Borrowed'?($v['holder']?:'Not named'):null]);AssetLog::create(['asset_id'=>$asset->id,'user_id'=>$r->user()->id,'status'=>$v['status'],'holder'=>$asset->holder]);Audit::log('Asset '.$v['status'],$asset->name);return back()->with('ok','Updated.');}}
