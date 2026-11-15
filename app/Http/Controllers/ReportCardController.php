<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Audit;
use App\Models\Module;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportCardController extends Controller
{
    public function show(Request $request, Student $student): View
    {
        $user = $request->user();
        $allowed = match ($user->role) {
            'head' => true,
            'student', 'parent' => $user->student_id === $student->id,
            'trainer' => $user->isClassTeacherFor($student->school_class_id),
            'dosb', 'dost' => $user->classes()->whereKey($student->school_class_id)->exists(),
            default => false,
        };
        abort_unless($allowed, 403);

        $modules = Module::where('school_class_id', $student->school_class_id)->orderBy('id')->get();
        $assessments = Assessment::whereIn('module_id', $modules->pluck('id'))
            ->whereNotNull('locked_at')
            ->orderBy('date')
            ->get()
            ->groupBy('module_id');
        $studentScores = Score::where('student_id', $student->id)->pluck('score', 'assessment_id');
        $rows = $modules->map(function (Module $module) use ($assessments, $studentScores): array {
            $total = 0;
            $maximum = 0;
            $tests = [];

            foreach ($assessments[$module->id] ?? [] as $assessment) {
                $score = $studentScores[$assessment->id] ?? null;
                $tests[] = ['a' => $assessment, 'v' => $score];
                if ($score !== null) {
                    $total += $score;
                    $maximum += $assessment->max;
                }
            }

            return ['m' => $module, 'tests' => $tests, 'p' => $maximum ? round($total / $maximum * 100) : null];
        });
        $gradedPercentages = $rows->pluck('p')->filter(fn ($percentage): bool => $percentage !== null);
        $average = $gradedPercentages->count() ? round($gradedPercentages->avg()) : null;
        $classRanks = $this->classAverages($student->school_class_id, $modules, $assessments);
        $rank = $average === null
            ? null
            : $classRanks->filter(fn ($peerAverage): bool => $peerAverage !== null && $peerAverage > $average)->count() + 1;
        $rankedStudents = $classRanks->filter(fn ($peerAverage): bool => $peerAverage !== null)->count();

        return view('report_card', [
            's' => $student->load('schoolClass'),
            'rows' => $rows,
            'avg' => $average,
            'att' => Attendance::where('student_id', $student->id)
                ->where('date', '>=', today()->subDays(89))
                ->get()
                ->countBy('status'),
            'rank' => $rank,
            'rankedStudents' => $rankedStudents,
        ]);
    }

    private function classAverages(int $schoolClassId, Collection $modules, Collection $assessments): Collection
    {
        $studentIds = Student::where('school_class_id', $schoolClassId)->pluck('id');
        $assessmentIds = $assessments->flatten()->pluck('id');
        $scores = Score::query()
            ->join('assessments', 'assessments.id', '=', 'scores.assessment_id')
            ->whereIn('scores.student_id', $studentIds)
            ->whereIn('scores.assessment_id', $assessmentIds)
            ->select('scores.student_id', 'assessments.module_id')
            ->selectRaw('sum(scores.score) as earned, sum(assessments.max) as available')
            ->groupBy('scores.student_id', 'assessments.module_id')
            ->get()
            ->groupBy('student_id');

        return $studentIds->mapWithKeys(function (int $studentId) use ($modules, $scores): array {
            $percentages = [];
            foreach ($modules as $module) {
                $result = ($scores[$studentId] ?? collect())->firstWhere('module_id', $module->id);
                if ($result && $result->available) {
                    $percentages[] = round($result->earned / $result->available * 100);
                }
            }

            return [$studentId => $percentages ? round(array_sum($percentages) / count($percentages)) : null];
        });
    }
}
