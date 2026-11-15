<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Outcome extends Model{protected $guarded=[];public $timestamps=false;protected $casts=['covered_on'=>'date'];}
