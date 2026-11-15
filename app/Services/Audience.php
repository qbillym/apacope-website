<?php
namespace App\Services;
use App\Models\{Announcement,User,Student};
class Audience{
public static function ctx(){return ['cls'=>Student::pluck('school_class_id','id')];}
public static function sees(User $u,Announcement $a,array $c):bool{
if($a->user_id===$u->id)return true;$t=$a->targets;
if($t->isEmpty()){if($a->audience==='School-wide')return true;return $u->classes()->where('name',$a->audience)->exists();}
$cid=$c['cls'][$u->student_id]??null;
foreach($t as $x){if($x->kind==='all')return true;if($x->kind==='role'&&$x->value===$u->role)return true;if($x->kind==='user'&&(int)$x->value===$u->id)return true;
if($x->kind==='cs'&&$u->role==='student'&&$cid&&(int)$x->value===$cid)return true;if($x->kind==='cp'&&$u->role==='parent'&&$cid&&(int)$x->value===$cid)return true;}
return false;}
public static function visible(User $u){$c=self::ctx();return Announcement::with('user','targets')->latest()->get()->filter(fn($a)=>self::sees($u,$a,$c))->values();}
public static function recipients(Announcement $a,$users,array $c){return $users->filter(fn($x)=>$x->id!==$a->user_id&&self::sees($x,$a,$c))->count();}}
