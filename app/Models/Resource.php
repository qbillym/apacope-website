<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Resource extends Model{protected $guarded=[];public function schoolClass(){return $this->belongsTo(SchoolClass::class);}public function user(){return $this->belongsTo(User::class);}public function comments(){return $this->hasMany(Comment::class,"resource_id")->oldest();}}
