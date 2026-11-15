<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class StaffAttendance extends Model{protected $guarded=[];protected $table='staff_attendance';public $timestamps=false;protected $casts=['date'=>'date'];}
