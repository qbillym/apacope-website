<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $trainerId = DB::table('users')->where('email', 'trainer@apacope.test')->value('id');
        $classId = DB::table('school_classes')->where('name', 'L5 SWD')->where('section', 'tvet')->value('id');

        if (! $trainerId || ! $classId) {
            return;
        }

        $phpLessons = DB::table('lessons')
            ->where('school_class_id', $classId)
            ->where('subject', 'PHP Programming');

        if ($phpLessons->exists()) {
            $phpLessons->update(['trainer_id' => $trainerId]);

            return;
        }

        $lesson = DB::table('lessons')
            ->where('school_class_id', $classId)
            ->whereIn('subject', ['Mobile App Development', 'Web Programming'])
            ->orderBy('day')
            ->orderBy('slot')
            ->first();

        if ($lesson) {
            DB::table('lessons')->where('id', $lesson->id)->update([
                'subject' => 'PHP Programming',
                'trainer_id' => $trainerId,
            ]);

            return;
        }

        DB::table('lessons')->insert([
            'school_class_id' => $classId,
            'day' => 1,
            'slot' => 1,
            'subject' => 'PHP Programming',
            'room' => 'Room 1',
            'trainer_id' => $trainerId,
        ]);
    }

    public function down(): void
    {
    }
};
