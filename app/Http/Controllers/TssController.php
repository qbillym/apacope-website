<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attachment;
use App\Models\Audit;
use App\Models\Module;
use App\Models\Outcome;
use App\Models\Score;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TssController extends Controller
{
    private function authorizeTss(User $user): void
    {
        abort_unless(
            $user->role === 'dost'
            || ($user->role === 'trainer' && $user->classes()->where('section', 'tvet')->exists()),
            403
        );
    }

    private function inScope(User $user, int $classId): bool
    {
        return $user->classes()->where('id', $classId)->where('section', 'tvet')->exists();
    }

    private function canManageModule(User $user, Module $module): bool
    {
        return $this->inScope($user, $module->school_class_id)
            && (int) $module->trainer_id === $user->id;
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->authorizeTss($user);
        $classes = $user->classes()->where('section', 'tvet')->orderBy('id')->get();
        $class = $classes->firstWhere('id', $request->integer('class')) ?? $classes->first();
        $isClassTeacher = $class && $user->isClassTeacherFor($class->id);
        $modules = $class
            ? Module::with('trainer')
                ->where('school_class_id', $class->id)
                ->when(
                    $user->role === 'trainer' && ! $isClassTeacher,
                    fn ($query) => $query->where('trainer_id', $user->id)
                )
                ->orderBy('id')
                ->get()
            : collect();
        $outcomes = Outcome::whereIn('module_id', $modules->pluck('id'))->orderBy('id')->get()->groupBy('module_id');
        $practicalAssessments = $class
            ? Assessment::with('module')
                ->whereIn('module_id', $modules->pluck('id'))
                ->where('kind', 'Practical')
                ->whereNotNull('locked_at')
                ->orderBy('date')
                ->get()
            : collect();
        $scores = Score::whereIn('assessment_id', $practicalAssessments->pluck('id'))->get()->groupBy('student_id');
        $students = $class ? $class->students()->orderBy('name')->get() : collect();
        $attachments = $class && $user->role === 'dost'
            ? Attachment::with('student')->whereIn('student_id', $students->pluck('id'))->latest()->get()
            : collect();

        return view('tss', [
            'classes' => $classes,
            'class' => $class,
            'mods' => $modules,
            'out' => $outcomes,
            'pa' => $practicalAssessments,
            'sc' => $scores,
            'students' => $students,
            'att' => $attachments,
        ]);
    }

    public function outcome(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'dost', 403);
        $values = $request->validate([
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'title' => ['required', 'string', 'max:200'],
        ]);
        $module = Module::findOrFail($values['module_id']);
        abort_unless($this->inScope($user, $module->school_class_id), 403);
        $outcome = Outcome::create($values);
        Audit::log('Learning outcome added', $module->name.' · '.$outcome->title);

        return back()->with('ok', 'Learning outcome added.');
    }

    public function updateOutcome(Request $request, Outcome $outcome): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'dost', 403);
        $module = Module::findOrFail($outcome->module_id);
        abort_unless($this->inScope($user, $module->school_class_id), 403);
        $values = $request->validate(['title' => ['required', 'string', 'max:200']]);
        $outcome->update($values);
        Audit::log('Learning outcome updated', $module->name.' · '.$outcome->title);

        return back()->with('ok', 'Learning outcome updated.');
    }

    public function destroyOutcome(Request $request, Outcome $outcome): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'dost', 403);
        $module = Module::findOrFail($outcome->module_id);
        abort_unless($this->inScope($user, $module->school_class_id), 403);
        Audit::log('Learning outcome deleted', $module->name.' · '.$outcome->title);
        $outcome->delete();

        return back()->with('ok', 'Learning outcome deleted.');
    }

    public function toggle(Request $request, Outcome $outcome): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'trainer', 403);
        $module = Module::findOrFail($outcome->module_id);
        abort_unless($this->canManageModule($user, $module), 403);
        $covered = ! $outcome->covered;
        $outcome->update(['covered' => $covered, 'covered_on' => $covered ? today() : null]);
        Audit::log($covered ? 'Learning outcome covered' : 'Learning outcome reopened', $module->name.' · '.$outcome->title);

        return back()->with('ok', $covered ? 'Marked as covered.' : 'Marked as not covered.');
    }

    public function attach(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'dost', 403);
        $values = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'company' => ['required', 'string', 'max:150'],
            'supervisor' => ['nullable', 'string', 'max:100'],
            'start' => ['required', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'status' => ['required', 'in:Planned,Ongoing,Completed'],
        ]);
        $student = Student::findOrFail($values['student_id']);
        abort_unless($this->inScope($user, $student->school_class_id), 403);
        Attachment::create($values);
        Audit::log('Industrial attachment added', $student->name.' · '.$values['company']);

        return back()->with('ok', 'Attachment recorded.');
    }

    public function attachUpdate(Request $request, Attachment $attachment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'dost', 403);
        $student = Student::findOrFail($attachment->student_id);
        abort_unless($this->inScope($user, $student->school_class_id), 403);
        $values = $request->validate([
            'status' => ['required', 'in:Planned,Ongoing,Completed'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $attachment->update($values + ['report' => $request->boolean('report')]);

        return back()->with('ok', 'Attachment updated.');
    }

    public function attachDelete(Request $request, Attachment $attachment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'dost', 403);
        $student = Student::findOrFail($attachment->student_id);
        abort_unless($this->inScope($user, $student->school_class_id), 403);
        $attachment->delete();

        return back()->with('ok', 'Deleted.');
    }
}
