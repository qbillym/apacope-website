<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $guarded = [];
    protected $casts = ['options' => 'json'];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function answers()
    {
        return $this->hasMany(QuizAnswer::class);
    }
}
