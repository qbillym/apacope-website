<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Audit extends Model{protected $table='audit_logs';protected $guarded=[];public static function log($a,$d=''){static::create(['user_id'=>auth()->id(),'action'=>$a,'detail'=>$d]);}public function user(){return $this->belongsTo(User::class);}}
