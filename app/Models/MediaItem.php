<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class MediaItem extends Model{protected $guarded=[];public function user(){return $this->belongsTo(User::class);}}
