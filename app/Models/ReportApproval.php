<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportApproval extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function invalidateCurrentTerm(int $schoolClassId): void
    {
        static::query()
            ->where('school_class_id', $schoolClassId)
            ->where('academic_year', Setting::val('academic_year', date('Y')))
            ->where('term', Setting::val('term', 'Term 2'))
            ->delete();
    }
}
