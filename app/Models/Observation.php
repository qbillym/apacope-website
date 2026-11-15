<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Observation extends Model{protected $guarded=[];protected $casts=['date'=>'date'];public function trainer(){return $this->belongsTo(User::class,'trainer_id');}public function observer(){return $this->belongsTo(User::class,'observer_id');}}
