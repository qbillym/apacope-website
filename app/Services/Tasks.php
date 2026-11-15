<?php

namespace App\Services;

use App\Models\AccountRequest;
use App\Models\Assessment;
use App\Models\Asset;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Fee;
use App\Models\FeeMessage;
use App\Models\MediaItem;
use App\Models\Module;
use App\Models\PasswordRequest;
use App\Models\Resource;
use App\Models\SchoolClass;
use App\Models\SessionPlan;
use App\Models\Setting;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Tasks
{
    public static function for(User $u): array
    {
        $t = [];
        $add = function ($txt, $url, $lvl = 'warn') use (&$t) {
            $t[] = ['t' => $txt, 'u' => $url, 'l' => $lvl];
        };
        $ids = $u->classes()->pluck('id');
        $th = Setting::th();
        $wk = now()->isWeekday();
        switch ($u->role) {
            case 'trainer':
                $classTeacherIds = SchoolClass::where('trainer_id', $u->id)->pluck('id');
                foreach (SchoolClass::whereIn('id', $classTeacherIds)->get() as $c) {
                    if ($wk && ! Attendance::where('school_class_id', $c->id)->whereDate('date', today())->exists()) {
                        $add("Take today's attendance for {$c->name}", "/attendance?class={$c->id}");
                    }
                }
                $moduleIds = Module::whereIn('school_class_id', $ids)->where(fn ($q) => $q->where('trainer_id', $u->id)->orWhereIn('school_class_id', $classTeacherIds))->pluck('id');
                $p = Assessment::whereIn('module_id', $moduleIds)->whereNull('locked_at');
                $n = (clone $p)->count();
                if ($n) {
                    $o = (clone $p)->whereDate('deadline', '<', today())->count();
                    $add("Enter marks for $n test(s)".($o ? " ($o overdue)" : ''), '/marks', $o ? 'bad' : 'warn');
                }
                $assignments = Assignment::whereIn('school_class_id', $ids)->whereExists(fn ($query) => $query->selectRaw('1')->from('modules')->whereColumn('modules.school_class_id', 'assignments.school_class_id')->whereColumn('modules.name', 'assignments.module')->whereIn('modules.id', $moduleIds));
                $g = Submission::whereNull('grade')->whereIn('assignment_id', (clone $assignments)->select('id'))->count();
                if ($g) {
                    $add("Mark $g submitted assignment(s)", '/assignments');
                }
                $sp = SessionPlan::where('user_id', $u->id)->where('delivered', 0)->whereDate('date', '<=', today())->count();
                if ($sp) {
                    $add("Update $sp session plan(s): mark them delivered or replan", '/plans');
                }
                break;
            case 'dosb':case 'dost':
                $vis = Absence::visible(Absence::rows($ids), $th)->count();
                if ($vis) {
                    $add("$vis student(s) need absence follow-up", '/followup', 'bad');
                }
                $rq = AccountRequest::whereIn('school_class_id', $ids)->where('status', 'pending')->count();
                if ($rq) {
                    $add("Approve $rq student account request(s)", '/requests');
                }
                $mi = MediaItem::where('status', 'pending')->whereIn('id', DB::table('media_classes')->whereIn('school_class_id', $ids)->pluck('media_item_id'))->count();
                if ($mi) {
                    $add("Approve $mi media item(s) from students", '/media');
                }
                $od = Assessment::whereIn('school_class_id', $ids)->whereNull('locked_at')->whereDate('deadline', '<', today())->count();
                if ($od) {
                    $add("$od test(s) are past their mark-entry deadline", '/supervision', 'bad');
                }
                $pl = SessionPlan::whereIn('school_class_id', $ids)->where('delivered', 0)->whereDate('date', '<', today())->count();
                if ($pl) {
                    $add("$pl session plan(s) are overdue", '/supervision');
                }
                $tr = $u->classes()->pluck('trainer_id')->filter();
                if ($wk && $tr->count() > StaffAttendance::whereDate('date', today())->whereIn('user_id', $tr)->count()) {
                    $add("Mark today's staff attendance for your trainers", '/staff-attendance');
                }
                break;
            case 'head':
                $cs = Absence::rows($ids)->where('absent', '>=', $th[2])->count();
                if ($cs) {
                    $add("$cs student(s) reached the management-review absence level", '/followup', 'bad');
                }
                $st = User::whereIn('role', ['dosb', 'dost', 'trainer', 'accountant', 'storekeeper'])->pluck('id');
                if ($wk && StaffAttendance::whereDate('date', today())->whereIn('user_id', $st)->count() < $st->count()) {
                    $add("Mark today's staff attendance", '/staff-attendance');
                }
                $mi = MediaItem::where('status', 'pending')->count();
                if ($mi) {
                    $add("$mi media item(s) are waiting for approval", '/media');
                }
                $od = Assessment::whereNull('locked_at')->whereDate('deadline', '<', today())->count();
                if ($od) {
                    $add("$od test(s) are past their mark-entry deadline", '/supervision', 'bad');
                }
                break;
            case 'accountant':
                $pp = Fee::whereColumn('invoice', '>', 'paid')->whereNotNull('promise_date')->whereDate('promise_date', '<', today())->count();
                if ($pp) {
                    $add("$pp parent(s) passed their promised payment date", '/fees', 'bad');
                }
                $n = Fee::whereColumn('invoice', '>', 'paid')->count();
                if ($n) {
                    $add("$n student(s) have a fee balance", '/fees');
                }
                break;
            case 'storekeeper':
                $d = Asset::whereIn('status', ['Damaged', 'In maintenance'])->count();
                if ($d) {
                    $add("$d asset(s) are damaged or in maintenance", '/inventory');
                }
                $b = Asset::where('status', 'Borrowed')->count();
                if ($b) {
                    $add("$b asset(s) are out on loan", '/inventory', 'info');
                }
                break;
            case 'admin':
                $rq = PasswordRequest::where('status', 'pending')->count();
                if ($rq) {
                    $add("$rq password reset request(s)", '/admin');
                }
                $last = collect(glob(Backup::dir().'/*.json.gz'))->map(fn ($f) => filemtime($f))->max();
                if (! $last || $last < now()->subDays(2)->timestamp) {
                    $add('No backup in the last 2 days. Make one now.', '/backups', 'bad');
                }
                break;
            case 'student':case 'parent':
                $s = Student::find($u->student_id);
                if (! $s) {
                    break;
                }
                $as = Assignment::where('school_class_id', $s->school_class_id)->whereDate('due_date', '>=', today())->whereNotIn('id', Submission::where('student_id', $s->id)->pluck('assignment_id'))->count();
                if ($as) {
                    $add("$as assignment(s) not yet submitted", '/assignments');
                }
                if ($u->role === 'student') {
                    $nm = Resource::where('school_class_id', $s->school_class_id)->where('created_at', '>=', now()->subDays(7))->count();
                    if ($nm) {
                        $add("$nm new learning material(s) this week", '/materials', 'info');
                    }
                } else {
                    $f = Fee::where('student_id', $s->id)->first();
                    if ($f && $f->invoice > $f->paid) {
                        $add('Fee balance to pay: '.number_format($f->invoice - $f->paid).' RWF', '/home');
                    }$m = FeeMessage::where('student_id',$s->id)->where('created_at','>=',now()->subDays(7))->count();
                    if ($m) {
                        $add("$m message(s) from the school this week",'/home','info');
                    }
                }
                $add('View and print the report card','/report/'.$s->id,'info');
                break;
        }

        return $t;
    }
}
