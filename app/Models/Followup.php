<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Followup extends Model{protected $guarded=[];protected $casts=['date'=>'date'];public function student(){return $this->belongsTo(Student::class);}public function user(){return $this->belongsTo(User::class);}}
