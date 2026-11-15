<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Student extends Model{protected $guarded=[];public function schoolClass(){return $this->belongsTo(SchoolClass::class);}public function attendances(){return $this->hasMany(Attendance::class);}public function fee(){return $this->hasOne(Fee::class);}public function guardians(){return $this->hasMany(Guardian::class);}
public function level(){$c=$this->schoolClass;return $c->level?:(preg_match('/^L(\d)/',$c->name,$m)?'Level '.$m[1]:$c->name);}
public function trade(){$c=$this->schoolClass;return $c->trade?:(preg_match('/^L\d /',$c->name)?'Software Development':'General education');}}
