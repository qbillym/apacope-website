<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Assignment extends Model{protected $guarded=[];protected $casts=['due_date'=>'datetime','is_quiz'=>'boolean'];public function user(){return $this->belongsTo(User::class);}public function schoolClass(){return $this->belongsTo(SchoolClass::class);}public function submissions(){return $this->hasMany(Submission::class);}public function quizQuestions(){return $this->hasMany(QuizQuestion::class);}}
