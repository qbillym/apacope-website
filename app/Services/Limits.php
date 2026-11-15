<?php
namespace App\Services;
class Limits{
public static function bytes($v):float{$v=trim((string)$v);if($v==='')return 0;$n=(float)$v;return match(strtolower(substr($v,-1))){'g'=>$n*1073741824,'m'=>$n*1048576,'k'=>$n*1024,default=>$n};}
public static function mb():int{$a=self::bytes(ini_get('upload_max_filesize'));$b=self::bytes(ini_get('post_max_size'));$m=$b>0?min($a?:$b,$b):$a;return (int)floor($m/1048576);}}
