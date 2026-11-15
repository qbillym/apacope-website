<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class SessionPlan extends Model{protected $guarded=[];protected $casts=['date'=>'date'];public function schoolClass(){return $this->belongsTo(SchoolClass::class);}public function user(){return $this->belongsTo(User::class);}}
