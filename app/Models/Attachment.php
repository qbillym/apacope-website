<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Attachment extends Model{protected $guarded=[];protected $casts=['start'=>'date','end'=>'date'];public function student(){return $this->belongsTo(Student::class);}}
