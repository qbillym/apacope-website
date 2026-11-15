<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class SchoolClass extends Model{protected $guarded=[];public function students(){return $this->hasMany(Student::class);}public function trainer(){return $this->belongsTo(User::class,"trainer_id");}}
