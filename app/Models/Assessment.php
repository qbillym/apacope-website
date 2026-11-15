<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Assessment extends Model{protected $guarded=[];protected $casts=['date'=>'date','locked_at'=>'datetime','deadline'=>'date'];public function module(){return $this->belongsTo(Module::class);}}
