<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Fee;
use App\Models\FeeMessage;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Outcome;
use App\Models\Payment;
use App\Models\Period;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\Absence;
use App\Services\Audience;
use App\Services\Tasks;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    private function rate($rows)
    {
        return $rows->count() ? round($rows->whereIn('status', ['P', 'L'])->count() / $rows->count() * 100) : 0;
    }

    private function news($u)
    {
        return Audience::visible($u)->take(3);
    }

    private function today(Collection $ids, ?int $trainerId = null, array $classTeacherIds = [], array $classes = [], array $modules = []): Collection
    {
        $d = min(max(now()->dayOfWeekIso, 1), 5);
        $p = Period::whereIn('school_class_id', $ids)->get()->keyBy(fn ($x) => $x->school_class_id.'-'.$x->slot);
        $query = Lesson::whereIn('school_class_id', $ids)->where('day', $d);
        if ($trainerId !== null) {
            $query->where(function ($q) use ($trainerId, $classTeacherIds) {
                $q->where('trainer_id', $trainerId)->orWhereIn('school_class_id', $classTeacherIds);
            });
        }

        $lessons = $query->orderBy('slot')->get();
        $teacherNames = $trainerId !== null
            ? User::whereIn('id', $lessons->pluck('trainer_id')->filter())->pluck('name', 'id')
            : collect();

        return $lessons->each(function ($l) use ($p, $classes, $modules, $teacherNames) {
            $k = $l->school_class_id.'-'.$l->slot;
            $l->time = isset($p[$k]) ? $p[$k]->start.' – '.$p[$k]->end : 'Period '.$l->slot;
            $l->className = $classes[$l->school_class_id] ?? '';
            $l->moduleId = $modules[$l->school_class_id.'|'.$l->subject] ?? null;
            $l->teacherName = $teacherNames[$l->trainer_id] ?? '';
        });
    }

    private function bad($ids, $min)
    {
        $th = Setting::th();
        $r = Absence::rows($ids);

        return $min >= $th[2] ? $r->where('absent', '>=', $min)->sortByDesc('absent')->values() : Absence::visible($r, $th);
    }

    public function index(Request $r)
    {
        $u = $r->user();
        $th = Setting::th();
        $ids = $u->classes()->pluck('id');
        $d = ['th' => $th, 'news' => $this->news($u)];
        $d['tasks'] = Tasks::for($u);
        if (in_array($u->role, ['head', 'dosb', 'dost'])) {
            $last = Attendance::whereIn('school_class_id', $ids)->max('date');
            $rows = Attendance::whereIn('school_class_id', $ids)->whereDate('date', $last)->get();
            $d += ['students' => Student::whereIn('school_class_id', $ids)->count(), 'rate' => $this->rate($rows), 'absent' => $rows->where('status', 'A')->count(), 'chronic' => $this->bad($ids, $th[2])->count(), 'alerts' => $this->bad($ids, $th[0])->take(5), 'last' => $last, 'pres' => $rows->where('status', 'P')->count(), 'late' => $rows->where('status', 'L')->count(), 'exc' => $rows->where('status', 'E')->count()];
            $cn = $u->classes()->pluck('name', 'id');
            $d['avgs'] = Score::join('assessments', 'assessments.id', '=', 'scores.assessment_id')->whereIn('assessments.school_class_id', $ids)->selectRaw('assessments.school_class_id as c, sum(scores.score) as s, sum(assessments.max) as m')->groupBy('assessments.school_class_id')->get()->map(fn ($x) => ['n' => $cn[$x->c] ?? '', 'p' => $x->m ? round($x->s / $x->m * 100) : 0])->sortBy('n')->values();
            $d['failed'] = Score::join('assessments', 'assessments.id', '=', 'scores.assessment_id')->whereIn('assessments.school_class_id', $ids)->selectRaw('assessments.module_id as mo, scores.student_id as st, sum(scores.score) as s, sum(assessments.max) as m')->groupBy('assessments.module_id', 'scores.student_id')->get()->filter(fn ($x) => $x->m && $x->s / $x->m < .5)->count();
            $d['pend'] = Assessment::whereIn('school_class_id', $ids)->whereNull('locked_at')->count();
            $d['noreg'] = now()->isWeekday() ? count($ids) - Attendance::whereIn('school_class_id', $ids)->whereDate('date', today())->distinct()->count('school_class_id') : 0;
            $d['byclass'] = $u->classes()->withCount('students')->orderBy('id')->get();
            $oc = Outcome::join('modules', 'modules.id', '=', 'outcomes.module_id')->whereIn('modules.school_class_id', $ids)->selectRaw('count(*) as t, sum(case when outcomes.covered then 1 else 0 end) as c')->first();
            $d['progress'] = $oc && $oc->t ? round($oc->c / $oc->t * 100) : null;
            $names = ['nursery' => 'Nursery', 'primary' => 'Primary', 'olevel' => 'O-Level', 'tvet' => 'TVET Software Development'];
            $d['sections'] = $u->classes()->get()->groupBy('section')->mapWithKeys(fn ($g, $k) => [$names[$k] => Student::whereIn('school_class_id', $g->pluck('id'))->count()]);
            $days = Attendance::whereIn('school_class_id', $ids)->distinct()->orderByDesc('date')->limit(5)->pluck('date')->map(fn ($x) => $x->toDateString())->reverse()->values();
            $all = Attendance::whereIn('school_class_id', $ids)->whereIn('date', $days)->get()->groupBy(fn ($a) => $a->school_class_id.'|'.$a->date->toDateString());
            $d['days'] = $days;
            $d['heat'] = $u->classes()->get()->map(fn ($c) => [$c->name, $days->map(fn ($x) => ($g = $all[$c->id.'|'.$x] ?? null) ? $this->rate($g) : null)]);
        } elseif ($u->role === 'trainer') {
            $trainerClasses = $u->classes()->withCount('students')->orderBy('name')->get();
            $classTeacherIds = SchoolClass::where('trainer_id', $u->id)->pluck('id');
            $visibleModules = Module::with('schoolClass')->whereIn('school_class_id', $trainerClasses->pluck('id'))->where(fn ($q) => $q->where('trainer_id', $u->id)->orWhereIn('school_class_id', $classTeacherIds))->orderBy('name')->get();
            $moduleIds = $visibleModules->keyBy(fn ($m) => $m->school_class_id.'|'.$m->name)->map(fn ($m) => $m->id)->all();
            $classNames = $trainerClasses->pluck('name', 'id')->all();
            $lessons = $this->today($trainerClasses->pluck('id'), $u->id, $classTeacherIds->all(), $classNames, $moduleIds);
            $c = $trainerClasses->first();
            $d += ['class' => $c, 'classes' => $trainerClasses, 'modules' => $visibleModules, 'isClassTeacher' => $classTeacherIds->isNotEmpty(), 'students' => $trainerClasses->sum('students_count'), 'present' => 0, 'absent' => 0, 'alerts' => $classTeacherIds->isNotEmpty() ? $this->bad($classTeacherIds, $th[0])->take(5) : collect(), 'lessons' => $lessons, 'last' => null];
        } elseif (in_array($u->role, ['student', 'parent'])) {
            $s = Student::with('schoolClass')->find($u->student_id);
            $m = Score::where('student_id', $s?->id)->join('assessments', 'assessments.id', '=', 'scores.assessment_id')->selectRaw('sum(scores.score) as s,sum(assessments.max) as m')->first();
            $a = Attendance::where('student_id', $s?->id)->get();
            $d += ['stu' => $s, 'rate' => $this->rate($a), 'avg' => ($m && $m->m) ? round($m->s / $m->m * 100) : 0, 'lessons' => $this->today($ids), 'fee' => $s?->fee, 'msgs' => $u->role === 'parent' ? FeeMessage::where('student_id', $s?->id)->latest()->take(8)->get() : collect(), 'recent' => Attendance::where('student_id', $s?->id)->orderByDesc('date')->take(14)->get(), 'absm' => Attendance::where('student_id', $s?->id)->where('status', 'A')->whereMonth('date', now()->month)->whereYear('date', now()->year)->count(), 'pays' => Payment::where('student_id', $s?->id)->latest()->get(), 'assign' => $s ? Assignment::where('school_class_id', $s->school_class_id)->whereDate('due_date', '>=', today())->orderBy('due_date')->take(5)->get() : collect()];
        } elseif ($u->role === 'accountant') {
            $t = Fee::selectRaw('sum(invoice) i,sum(paid) p,sum(case when invoice>paid then 1 else 0 end) n')->first();
            $d += ['inv' => $t->i, 'paid' => $t->p, 'n' => $t->n];
        } elseif ($u->role === 'admin') {
            $d['users'] = User::orderBy('role')->get();
        }

        return view('dashboard', ['d' => $d]);
    }
}
