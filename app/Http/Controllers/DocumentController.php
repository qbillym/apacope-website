<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use App\Models\{Document,Audit};
class DocumentController extends Controller{
private function ok($r){abort_unless(in_array($r->user()->role,['head','dosb','dost']),403);}
public function index(Request $r){$this->ok($r);return view('documents',['list'=>Document::with('user')->latest()->get()]);}
public function store(Request $r){$this->ok($r);$v=$r->validate(['type'=>'required|in:Meeting minutes,Official letter,Staff form,Management report','title'=>'required|max:150','body'=>'required|max:8000']);$d=Document::create($v+['user_id'=>$r->user()->id]);Audit::log('Document created',$v['type'].' · '.$v['title']);return redirect('/documents/'.$d->id)->with('ok','Saved. You can print it or save it as PDF.');}
public function show(Request $r,Document $document){$this->ok($r);return view('document_show',['doc'=>$document->load('user')]);}
public function destroy(Request $r,Document $document){$this->ok($r);abort_unless($document->user_id===$r->user()->id,403);$document->delete();return redirect('/documents')->with('ok','Deleted.');}}
