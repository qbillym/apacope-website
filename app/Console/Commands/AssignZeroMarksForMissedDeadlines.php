<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\Assessment;
use App\Models\Score;
use App\Models\Submission;
use App\Models\Student;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:assign-zero-marks-for-missed-deadlines')]
#[Description('Assign zero marks to students who missed assignment deadlines')]
class AssignZeroMarksForMissedDeadlines extends Command
{
    public function handle()
    {
        $this->info('Checking for missed deadlines...');

        $assignments = Assignment::where('is_quiz', false)
            ->where('due_date', '<', now())
            ->whereNotNull('assessment_id')
            ->with(['schoolClass'])
            ->get();

        $count = 0;

        foreach ($assignments as $assignment) {
            $students = Student::where('school_class_id', $assignment->school_class_id)->get();

            foreach ($students as $student) {
                $hasSubmitted = Submission::where('assignment_id', $assignment->id)
                    ->where('student_id', $student->id)
                    ->exists();

                $hasScore = Score::where('assessment_id', $assignment->assessment_id)
                    ->where('student_id', $student->id)
                    ->exists();

                if (!$hasSubmitted && !$hasScore) {
                    DB::transaction(function () use ($assignment, $student) {
                        Score::create([
                            'assessment_id' => $assignment->assessment_id,
                            'student_id' => $student->id,
                            'score' => 0,
                        ]);

                        Submission::create([
                            'assignment_id' => $assignment->id,
                            'student_id' => $student->id,
                            'content' => 'No submission - deadline missed',
                        ]);
                    });

                    $count++;
                    $this->line("Assigned 0 marks to student {$student->name} for {$assignment->title}");
                }
            }
        }

        $this->info("Assigned zero marks to {$count} students for missed deadlines.");
        return 0;
    }
}
