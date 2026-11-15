<?php
namespace App\Services;
use App\Models\RoleDeny;
class Perm{static $c=null;
public static function denied($role,$feature):bool{self::$c??=RoleDeny::all()->groupBy('role')->map(fn($g)=>$g->pluck('feature')->all())->all();return in_array($feature,self::$c[$role]??[]);}}
