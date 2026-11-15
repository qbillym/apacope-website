<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Portfolio extends Model{protected $guarded=[];public function student(){return $this->belongsTo(Student::class);}public function owner(){return $this->belongsTo(User::class,'user_id');}}
