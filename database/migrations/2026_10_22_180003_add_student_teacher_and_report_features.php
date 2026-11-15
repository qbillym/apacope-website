<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('sdms_no')->nullable()->unique();
        });

        Schema::table('modules', function (Blueprint $table): void {
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('report_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year', 20);
            $table->string('term', 30);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at');
            $table->timestamps();
            $table->unique(['school_class_id', 'academic_year', 'term']);
        });

        $trainerId = DB::table('users')->where('email', 'trainer@apacope.test')->value('id');
        $classId = DB::table('school_classes')->where('name', 'L5 SWD')->where('section', 'tvet')->value('id');

        if ($trainerId && $classId) {
            $moduleId = DB::table('modules')
                ->where('school_class_id', $classId)
                ->where('name', 'PHP Programming')
                ->value('id');

            if ($moduleId) {
                DB::table('modules')->where('id', $moduleId)->update(['trainer_id' => $trainerId]);
            } else {
                DB::table('modules')->insert([
                    'school_class_id' => $classId,
                    'name' => 'PHP Programming',
                    'trainer_id' => $trainerId,
                ]);
            }

            DB::table('school_classes')
                ->where('trainer_id', $trainerId)
                ->where('id', '!=', $classId)
                ->update(['trainer_id' => null]);
            DB::table('school_classes')->where('id', $classId)->update(['trainer_id' => $trainerId]);
            DB::table('lessons')
                ->where('trainer_id', $trainerId)
                ->where(fn ($query) => $query->where('school_class_id', '!=', $classId)
                    ->orWhere('subject', '!=', 'PHP Programming'))
                ->update(['trainer_id' => null]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_approvals');
        Schema::table('modules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('trainer_id');
        });
        Schema::table('students', function (Blueprint $table): void {
            $table->dropUnique(['sdms_no']);
            $table->dropColumn('sdms_no');
        });
    }
};
