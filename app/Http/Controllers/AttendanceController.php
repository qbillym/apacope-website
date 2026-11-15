<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Audit;
use App\Models\FeeMessage;
use App\Models\Register;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    private function state($cid, $date)
    {
        $g = Register::with('user')->where('school_class_id', $cid)->whereDate('date', $date)->first();
        if ($g) {
            return $g->saved_at ? $g : null;
        }

        return Attendance::where('school_class_id', $cid)->whereDate('date', $date)->exists() ? new Register(['saved_at' => now()]) : null;
    }

    public function index(Request $r)
    {
        $u = $r->user();
        abort_unless(in_array($u->role, ['trainer', 'dosb', 'dost'], true) && ($u->role !== 'trainer' || $u->isClassTeacher()), 403);
        $classes = $u->classes()->when($u->role === 'trainer', fn ($q) => $q->where('trainer_id', $u->id))->orderBy('id')->get();
        $class = $classes->firstWhere('id', (int) $r->class) ?? $classes->first();
        $date = $r->date ?: today()->toDateString();
        $students = $class ? $class->students()->orderBy('name')->get() : collect();
        $marks = $class ? Attendance::where('school_class_id', $class->id)->whereDate('date', $date)->pluck('status', 'student_id') : collect();
        $locked = $class ? $this->state($class->id, $date) : null;
        $hist = $class ? Attendance::where('school_class_id', $class->id)->selectRaw("date, count(*) as n, sum(case when status='A' then 1 else 0 end) as a, sum(case when status in ('P','L') then 1 else 0 end) as p")->groupBy('date')->orderByDesc('date')->take(20)->get() : collect();
        $period = in_array($r->period, ['week', 'month', 'term', 'year']) ? $r->period : 'month';
        $dd = ['week' => 6, 'month' => 29, 'term' => 89, 'year' => 364][$period];
        $byS = $class ? Attendance::where('school_class_id', $class->id)->where('date', '>=', today()->subDays($dd))->get()->groupBy('student_id') : collect();
        $sum = $students->map(function ($s) use ($byS) {
            $g = $byS[$s->id] ?? collect();
            $n = $g->count();
            $p = $g->whereIn('status', ['P', 'L'])->count();

            return ['s' => $s, 'n' => $n, 'p' => $p, 'a' => $g->where('status', 'A')->count(), 'e' => $g->where('status', 'E')->count(), 'pct' => $n ? round($p / $n * 100) : 0];
        });
        $reopened = $class ? Register::where('school_class_id', $class->id)->whereDate('date', $date)->whereNull('saved_at')->exists() : false;

        return view('attendance', compact('classes', 'class', 'date', 'students', 'marks', 'locked', 'hist', 'reopened', 'sum', 'period'));
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->role === 'trainer', 403);
        $r->validate(['class_id' => 'required|integer', 'date' => 'required|date|before_or_equal:today', 's' => 'required|array']);
        $class = $r->user()->classes()->findOrFail($r->class_id);
        abort_unless($r->user()->isClassTeacherFor($class->id), 403);
        if ($this->state($class->id, $r->date)) {
            return back()->with('err', 'This register is already saved and locked. Only the DOS can reopen it.');
        }
        $ok = $class->students()->pluck('id')->all();
        foreach ($r->s as $sid => $st) {
            if (! in_array((int) $sid, $ok) || ! in_array($st, ['P', 'A', 'L', 'E'])) {
                continue;
            }

            $attendance = Attendance::where('student_id', $sid)->whereDate('date', $r->date)->first();
            if ($attendance) {
                $attendance->update(['school_class_id' => $class->id, 'status' => $st]);
            } else {
                Attendance::create(['student_id' => $sid, 'school_class_id' => $class->id, 'date' => $r->date, 'status' => $st]);
            }
        }
        $nm = $class->students()->pluck('name', 'id');
        foreach ($r->s as $sid => $st) {
            if ($st === 'A' && isset($nm[$sid])) {
                FeeMessage::create(['student_id' => $sid, 'user_id' => $r->user()->id, 'channel' => 'Attendance', 'body' => $nm[$sid].' was marked absent on '.Carbon::parse($r->date)->format('d M Y').'. Please contact the school if there is a reason.']);
            }
        }
        $register = Register::where('school_class_id', $class->id)->whereDate('date', $r->date)->first();
        if ($register) {
            $register->update(['user_id' => $r->user()->id, 'saved_at' => now()]);
        } else {
            Register::create(['school_class_id' => $class->id, 'date' => $r->date, 'user_id' => $r->user()->id, 'saved_at' => now()]);
        }

        Audit::log('Attendance saved', $class->name.' · '.$r->date);

        return redirect("/attendance?class={$class->id}&date={$r->date}")->with('ok', 'Register saved and locked.');
    }

    public function reopen(Request $r)
    {
        abort_unless(in_array($r->user()->role, ['dosb', 'dost'], true), 403);
        $c = $r->user()->classes()->findOrFail($r->class_id);
        $register = Register::where('school_class_id', $c->id)->whereDate('date', $r->date)->first();
        if ($register) {
            $register->update(['saved_at' => null]);
        } else {
            Register::create(['school_class_id' => $c->id, 'date' => $r->date, 'saved_at' => null]);
        }

        Audit::log('Attendance reopened', $c->name.' · '.$r->date);

        return redirect("/attendance?class={$c->id}&date={$r->date}")->with('ok', 'Register reopened. The class teacher can now correct it, and it locks again when saved.');
    }
}
