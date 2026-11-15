<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->boolean('is_quiz')->default(false)->after('instructions');
            $table->text('quiz_data')->nullable()->after('is_quiz');
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->text('question');
            $table->string('type')->default('multiple_choice'); // multiple_choice, short_answer
            $table->json('options')->nullable(); // for multiple choice
            $table->string('correct_answer');
            $table->integer('points')->default(1);
            $table->timestamps();
            
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
        });

        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_question_id');
            $table->unsignedBigInteger('student_id');
            $table->text('answer');
            $table->boolean('is_correct')->default(false);
            $table->integer('points_earned')->default(0);
            $table->timestamps();
            
            $table->foreign('quiz_question_id')->references('id')->on('quiz_questions')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->unique(['quiz_question_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_questions');
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['is_quiz', 'quiz_data']);
        });
    }
};
