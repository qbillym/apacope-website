<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Module extends Model{public $timestamps=false;protected $guarded=[];public function schoolClass(){return $this->belongsTo(SchoolClass::class);}public function trainer(){return $this->belongsTo(User::class,'trainer_id');}}
