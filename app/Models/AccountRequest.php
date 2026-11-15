<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class AccountRequest extends Model{protected $guarded=[];public function schoolClass(){return $this->belongsTo(SchoolClass::class);}}
