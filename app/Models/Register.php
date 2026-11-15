<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Register extends Model{public $timestamps=false;protected $guarded=[];protected $casts=['date'=>'date','saved_at'=>'datetime'];public function user(){return $this->belongsTo(User::class);}}
