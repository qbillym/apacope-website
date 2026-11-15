<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Setting extends Model{public $incrementing=false;public $timestamps=false;protected $primaryKey='key';protected $keyType='string';protected $guarded=[];
public static function val($k,$d=''){return (string)(static::where('key',$k)->value('value')?:$d);}public static function th():array{$d=['th1'=>3,'th2'=>5,'th3'=>8];return array_map(fn($k)=>(int)(static::where('key',$k)->value('value')?:$d[$k]),array_keys($d));}}
