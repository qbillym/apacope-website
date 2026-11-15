<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Assignment;
use App\Models\Audit;
use App\Models\FeeMessage;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Resource;
use App\Models\Score;
use App\Models\SessionPlan;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarkController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['trainer', 'student', 'parent', 'dosb', 'dost'], true), 403);

        $classes = $user->classes()->orderBy('id')->get();
        $class = $classes->firstWhere('id', $request->integer('class')) ?? $classes->first();
        $allModules = $class
            ? Module::with('trainer')->where('school_class_id', $class->id)->orderBy('id')->get()
            : collect();
        $isClassTeacher = $user->role === 'trainer' && $class && $user->isClassTeacherFor($class->id);
        $managedModules = $user->role === 'trainer'
            ? $allModules->filter(fn (Module $classModule): bool => (int) $classModule->trainer_id === $user->id || $isClassTeacher)->values()
            : $allModules;
        if ($user->role === 'trainer') {
            $allModules = $managedModules;
        }
        $module = $managedModules->firstWhere('id', $request->integer('module')) ?? $managedModules->first();
        $students = $class ? $class->students()->orderBy('name')->get() : collect();

        if (in_array($user->role, ['student', 'parent'], true)) {
            $students = $students->where('id', $user->student_id)->values();
        }

        $assessments = $module
            ? Assessment::where('module_id', $module->id)->orderBy('date')->orderBy('id')->get()
            : collect();
        $scores = Score::whereIn('assessment_id', $assessments->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id');
        $lockedAssessments = Assessment::whereIn('module_id', $allModules->pluck('id'))
            ->whereNotNull('locked_at')
            ->get()
            ->groupBy('module_id');
        $allScores = Score::whereIn('assessment_id', $lockedAssessments->flatten()->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id');
        $summary = $students->map(function (Student $student) use ($allModules, $lockedAssessments, $allScores): array {
            $studentScores = ($allScores[$student->id] ?? collect())->keyBy('assessment_id');
            $modulePercentages = [];

            foreach ($allModules as $classModule) {
                $earned = 0;
                $available = 0;

                foreach ($lockedAssessments[$classModule->id] ?? [] as $assessment) {
                    if (isset($studentScores[$assessment->id])) {
                        $earned += $studentScores[$assessment->id]->score;
                        $available += $assessment->max;
                    }
                }

                $modulePercentages[$classModule->id] = $available ? round($earned / $available * 100) : null;
            }

            $graded = array_filter($modulePercentages, fn ($percentage): bool => $percentage !== null);

            return [
                's' => $student,
                'row' => $modulePercentages,
                'avg' => $graded ? round(array_sum($graded) / count($graded)) : null,
            ];
        });

        return view('marks', [
            'classes' => $classes,
            'class' => $class,
            'modules' => $allModules,
            'managedModules' => $managedModules,
            'mod' => $module,
            'students' => $students,
            'ass' => $assessments,
            'scores' => $scores,
            'summary' => $summary,
            'tr' => $user->role === 'trainer',
            'isClassTeacher' => $isClassTeacher,
        ]);
    }

    public function module(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['dosb', 'dost'], true), 403);

        $values = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'class_id' => ['required', 'integer'],
            'context' => ['nullable', Rule::in(['marks', 'tss'])],
        ]);
        $schoolClass = $user->classes()->findOrFail($values['class_id']);
        $module = Module::firstOrCreate([
            'school_class_id' => $schoolClass->id,
            'name' => trim($values['name']),
        ]);

        $destination = ($values['context'] ?? 'marks') === 'tss'
            ? "/tss?class={$schoolClass->id}"
            : "/marks?class={$schoolClass->id}&module={$module->id}";

        return redirect($destination)
            ->with('ok', $module->wasRecentlyCreated ? 'Subject added.' : 'That subject already exists for this class.');
    }

    public function updateModule(Request $request, Module $module): RedirectResponse
    {
        $user = $request->user();
        abort_unless(
            in_array($user->role, ['dosb', 'dost'], true)
            && $user->classes()->whereKey($module->school_class_id)->exists(),
            403
        );

        $values = $request->validate([
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('modules', 'name')->where('school_class_id', $module->school_class_id)->ignore($module->id),
            ],
        ]);
        $oldName = $module->name;
        $newName = trim($values['name']);

        DB::transaction(function () use ($module, $oldName, $newName): void {
            $module->update(['name' => $newName]);
            Lesson::where('school_class_id', $module->school_class_id)->where('subject', $oldName)->update(['subject' => $newName]);
            Assignment::where('school_class_id', $module->school_class_id)->where('module', $oldName)->update(['module' => $newName]);
            Resource::where('school_class_id', $module->school_class_id)->where('module', $oldName)->update(['module' => $newName]);
            SessionPlan::where('school_class_id', $module->school_class_id)->where('module', $oldName)->update(['module' => $newName]);
        });

        return back()->with('ok', 'Subject renamed. Its timetable and linked class materials now use the new name.');
    }

    public function destroyModule(Request $request, Module $module): RedirectResponse
    {
        $user = $request->user();
        abort_unless(
            in_array($user->role, ['dosb', 'dost'], true)
            && $user->classes()->whereKey($module->school_class_id)->exists(),
            403
        );

        $assessmentIds = Assessment::where('module_id', $module->id)->pluck('id');
        if (Score::whereIn('assessment_id', $assessmentIds)->exists()) {
            return back()->with('err', 'This subject has saved marks and cannot be deleted. Rename it or remove its marks through the approved school process.');
        }

        $assignmentIds = Assignment::where(function ($query) use ($module, $assessmentIds): void {
            $query->where('school_class_id', $module->school_class_id)
                ->where('module', $module->name)
                ->orWhereIn('assessment_id', $assessmentIds);
        })->pluck('id');
        if (Submission::whereIn('assignment_id', $assignmentIds)->exists()) {
            return back()->with('err', 'This subject has submitted student work and cannot be deleted. Rename it to preserve the submissions.');
        }

        $resourcePaths = Resource::where('school_class_id', $module->school_class_id)
            ->where('module', $module->name)
            ->whereNotNull('path')
            ->pluck('path');
        DB::transaction(function () use ($module, $assignmentIds): void {
            Assignment::whereIn('id', $assignmentIds)->delete();
            Resource::where('school_class_id', $module->school_class_id)->where('module', $module->name)->delete();
            SessionPlan::where('school_class_id', $module->school_class_id)->where('module', $module->name)->delete();
            Lesson::where('school_class_id', $module->school_class_id)->where('subject', $module->name)->delete();
            DB::table('outcomes')->where('module_id', $module->id)->delete();
            $module->delete();
        });

        foreach ($resourcePaths as $path) {
            if (Storage::disk('local')->exists($path) && ! Storage::disk('local')->delete($path)) {
                Log::error('Could not remove a learning material file for a deleted module.', [
                    'module_id' => $module->id,
                    'path' => $path,
                ]);

                return back()->with('err', 'The subject was deleted, but a learning material file could not be removed. Contact the system administrator.');
            }
        }

        Audit::log('Module deleted', $module->name.' · class '.$module->school_class_id);

        return back()->with('ok', 'Subject deleted with its timetable, unsubmitted assignments, materials and session plans.');
    }

    public function assessment(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'trainer', 403);

        $values = $request->validate([
            'module_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:80'],
            'kind' => ['required', Rule::in(['Formative', 'Summative', 'Quiz', 'Comprehensive', 'Practical'])],
            'max' => ['required', 'numeric', 'min:1', 'max:100'],
            'date' => ['required', 'date'],
            'deadline' => ['nullable', 'date'],
        ]);
        $module = Module::findOrFail($values['module_id']);
        abort_unless((int) $module->trainer_id === $user->id || $user->isClassTeacherFor($module->school_class_id), 404);

        $assessment = Assessment::create($values + [
            'module_id' => $module->id,
            'school_class_id' => $module->school_class_id,
            'user_id' => $user->id,
        ]);

        return redirect("/marks?class={$module->school_class_id}&module={$module->id}")
            ->with('ok', 'Test created. Enter the marks below.');
    }

    public function scores(Request $request, Assessment $assessment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'trainer', 403);
        $module = Module::where('school_class_id', $assessment->school_class_id)->findOrFail($assessment->module_id);
        abort_unless((int) $module->trainer_id === $user->id || $user->isClassTeacherFor($module->school_class_id), 404);

        $values = $request->validate([
            'm' => ['required', 'array'],
            'm.*' => ['nullable', 'numeric', 'min:0', 'max:'.$assessment->max],
        ]);
        $studentIds = Student::where('school_class_id', $assessment->school_class_id)->pluck('id');

        foreach ($values['m'] as $studentId => $score) {
            if (! $studentIds->contains((int) $studentId)) {
                abort(422, 'A submitted student is not in this class.');
            }
        }

        $isCorrection = $assessment->locked_at !== null;
        $names = Student::whereIn('id', $studentIds)->pluck('name', 'id');

        DB::transaction(function () use ($values, $assessment, $user, $names, $isCorrection): void {
            foreach ($values['m'] as $studentId => $score) {
                if ($score === null || $score === '') {
                    continue;
                }

                Score::updateOrCreate(
                    ['assessment_id' => $assessment->id, 'student_id' => $studentId],
                    ['score' => (float) $score]
                );

                if (! $isCorrection && (float) $score < $assessment->max * 0.5) {
                    FeeMessage::create([
                        'student_id' => $studentId,
                        'user_id' => $user->id,
                        'channel' => 'Marks',
                        'body' => ($names[$studentId] ?? 'Your child').' scored '.(float) $score.' out of '.$assessment->max.' in '.$assessment->title.'. Extra support may help.',
                    ]);
                }
            }

            $assessment->update(['locked_at' => now()]);
        });

        Audit::log($isCorrection ? 'Marks corrected' : 'Marks saved', $assessment->title);

        return redirect("/marks?class={$assessment->school_class_id}&module={$module->id}")
            ->with('ok', $isCorrection ? 'Marks updated. Totals and grades have been recalculated.' : 'Marks saved.');
    }
}
