<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Audit;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Period;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TimetableController extends Controller
{
    private const MAX_PERIODS = 12;

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['trainer', 'dosb', 'dost', 'head', 'student', 'parent'], true), 403);

        $classes = $user->classes()->orderBy('id')->get();
        if ($user->role === 'trainer') {
            $classTeacherIds = SchoolClass::where('trainer_id', $user->id)->pluck('id');
            $trainerLessons = Lesson::whereIn('school_class_id', $classes->pluck('id'))
                ->where(function ($query) use ($user, $classTeacherIds): void {
                    $query->where('trainer_id', $user->id)
                        ->orWhereIn('school_class_id', $classTeacherIds);
                })
                ->orderBy('day')
                ->orderBy('slot')
                ->get();
            $periodsByClassAndSlot = Period::whereIn('school_class_id', $classes->pluck('id'))
                ->get()
                ->keyBy(fn (Period $period): string => $period->school_class_id.'-'.$period->slot);
            $classNames = $classes->pluck('name', 'id');
            $trainerNames = User::whereIn('id', $trainerLessons->pluck('trainer_id')->filter())->pluck('name', 'id');
            $moduleIds = Module::whereIn('school_class_id', $classes->pluck('id'))
                ->where(fn ($query) => $query->where('trainer_id', $user->id)->orWhereIn('school_class_id', $classTeacherIds))
                ->get()
                ->keyBy(fn (Module $module): string => $module->school_class_id.'|'.$module->name)
                ->map(fn (Module $module): int => $module->id);
            $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'];
            $trainerLessons->each(function (Lesson $lesson) use ($periodsByClassAndSlot, $classNames, $trainerNames, $moduleIds, $dayNames): void {
                $period = $periodsByClassAndSlot[$lesson->school_class_id.'-'.$lesson->slot] ?? null;
                $lesson->time = $period ? $period->start.' – '.$period->end : 'Period '.$lesson->slot;
                $lesson->className = $classNames[$lesson->school_class_id] ?? '';
                $lesson->teacherName = $trainerNames[$lesson->trainer_id] ?? '';
                $lesson->dayName = $dayNames[$lesson->day] ?? 'Day '.$lesson->day;
                $lesson->moduleId = $moduleIds[$lesson->school_class_id.'|'.$lesson->subject] ?? null;
            });

            return view('timetable', [
                'classes' => $classes,
                'trainerView' => true,
                'trainerLessons' => $trainerLessons,
                'todayDay' => min(max(now()->dayOfWeekIso, 1), 5),
            ]);
        }

        $class = $classes->firstWhere('id', $request->integer('class')) ?? $classes->first();
        $edit = in_array($user->role, ['dosb', 'dost'], true);
        $lessonsQuery = Lesson::where('school_class_id', $class?->id);
        if ($user->role === 'trainer') {
            $lessonsQuery->where('trainer_id', $user->id);
        }

        $lessons = $lessonsQuery->get()->keyBy(fn (Lesson $lesson): string => $lesson->day.'-'.$lesson->slot);
        $periods = Period::where('school_class_id', $class?->id)->orderBy('slot')->get()->keyBy('slot');
        $modules = Module::where('school_class_id', $class?->id)->get();
        $subjects = $modules->pluck('name')
            ->merge(['Mathematics', 'English', 'Kinyarwanda', 'Science', 'Social Studies', 'Sport', 'Practical Lab', 'Break'])
            ->unique()
            ->values();
        $trainerIds = $this->trainerIds($classes);

        return view('timetable', [
            'trainers' => User::where('role', 'trainer')->whereIn('id', $trainerIds)->orderBy('name')->get(),
            'tn' => User::whereIn('id', $lessons->pluck('trainer_id')->filter())->pluck('name', 'id'),
            'classes' => $classes,
            'class' => $class,
            'L' => $lessons,
            'P' => $periods,
            'subjects' => $subjects,
            'moduleTrainers' => $modules->pluck('trainer_id', 'name'),
            'slots' => $edit ? range(1, self::MAX_PERIODS) : $periods->keys()->all(),
            'edit' => $edit,
            'trainerView' => false,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['dosb', 'dost'], true), 403);
        $values = $request->validate(['class_id' => ['required', 'integer']]);
        $schoolClass = $user->classes()->findOrFail($values['class_id']);
        $periodInputs = $request->input('p', []);
        $lessonInputs = $request->input('c', []);
        abort_unless(is_array($periodInputs) && is_array($lessonInputs), 422, 'Invalid timetable data.');

        $activePeriods = [];
        foreach (range(1, self::MAX_PERIODS) as $slot) {
            $start = (string) data_get($periodInputs, "{$slot}.start", '');
            $end = (string) data_get($periodInputs, "{$slot}.end", '');
            if ($start === '') {
                continue;
            }

            if (! preg_match('/^\d{2}:\d{2}$/', $start)
                || ! preg_match('/^\d{2}:\d{2}$/', $end)
                || $end <= $start
                || (int) substr($start, 0, 2) > 23
                || (int) substr($end, 0, 2) > 23
                || (int) substr($start, 3, 2) > 59
                || (int) substr($end, 3, 2) > 59) {
                return back()->with('err', "Period {$slot}: set a valid start time and a later end time.");
            }

            $activePeriods[$slot] = [$start, $end];
        }

        $periodTimes = array_values($activePeriods);
        usort($periodTimes, fn (array $left, array $right): int => strcmp($left[0], $right[0]));
        for ($index = 1; $index < count($periodTimes); $index++) {
            if ($periodTimes[$index][0] < $periodTimes[$index - 1][1]) {
                return back()->with('err', 'Period times cannot overlap.');
            }
        }

        $modules = Module::where('school_class_id', $schoolClass->id)->get();
        $allowedTrainerIds = $this->trainerIds($user->classes()->get());
        $pendingLessons = [];

        foreach ($lessonInputs as $key => $lessonInput) {
            if (! is_array($lessonInput)) {
                abort(422, 'Invalid lesson data.');
            }
            [$day, $slot] = array_pad(explode('-', (string) $key, 2), 2, null);
            $day = filter_var($day, FILTER_VALIDATE_INT);
            $slot = filter_var($slot, FILTER_VALIDATE_INT);
            if ($day === false || $slot === false || $day < 1 || $day > 5 || ! isset($activePeriods[$slot])) {
                continue;
            }

            $subject = trim((string) ($lessonInput['subject'] ?? ''));
            if ($subject === '') {
                continue;
            }
            if (mb_strlen($subject) > 80 || mb_strlen((string) ($lessonInput['room'] ?? '')) > 30) {
                return back()->with('err', 'Subject names must be 80 characters or fewer and rooms 30 characters or fewer.');
            }

            $assignedModule = $modules->firstWhere('name', $subject);
            $trainerId = $lessonInput['trainer'] ?? null;
            if ($trainerId === '' || $trainerId === null) {
                $trainerId = $assignedModule?->trainer_id;
            }
            if ($trainerId !== null) {
                $trainerId = filter_var($trainerId, FILTER_VALIDATE_INT);
                if ($trainerId === false || ! $allowedTrainerIds->contains((int) $trainerId)) {
                    return back()->with('err', 'Choose a trainer assigned to a class or subject in your section.');
                }
                if (! $assignedModule || (int) $assignedModule->trainer_id !== (int) $trainerId) {
                    return back()->with('err', 'Assign the trainer to this subject before adding them to its timetable lessons.');
                }
                if (Lesson::where('trainer_id', $trainerId)
                    ->where('day', $day)
                    ->where('slot', $slot)
                    ->where('school_class_id', '!=', $schoolClass->id)
                    ->exists()) {
                    return back()->with('err', 'That trainer already has a lesson at the same time in another class.');
                }
            }

            $pendingLessons[$day.'-'.$slot] = [
                'day' => (int) $day,
                'slot' => (int) $slot,
                'subject' => mb_substr($subject, 0, 80),
                'room' => mb_substr(trim((string) ($lessonInput['room'] ?? '')) ?: '-', 0, 30),
                'trainer_id' => $trainerId,
            ];
        }

        DB::transaction(function () use ($schoolClass, $activePeriods, $lessonInputs, $pendingLessons, $user): void {
            foreach (range(1, self::MAX_PERIODS) as $slot) {
                $period = ['school_class_id' => $schoolClass->id, 'slot' => $slot];
                if (isset($activePeriods[$slot])) {
                    Period::updateOrCreate($period, ['start' => $activePeriods[$slot][0], 'end' => $activePeriods[$slot][1]]);
                } else {
                    Period::where($period)->delete();
                    Lesson::where($period)->delete();
                }
            }

            foreach ($lessonInputs as $key => $lessonInput) {
                [$day, $slot] = array_pad(explode('-', (string) $key, 2), 2, null);
                $day = filter_var($day, FILTER_VALIDATE_INT);
                $slot = filter_var($slot, FILTER_VALIDATE_INT);
                if ($day === false || $slot === false || $day < 1 || $day > 5 || ! isset($activePeriods[$slot])) {
                    continue;
                }

                $subject = trim((string) ($lessonInput['subject'] ?? ''));
                $where = ['school_class_id' => $schoolClass->id, 'day' => $day, 'slot' => $slot];
                if ($subject === '') {
                    Lesson::where($where)->delete();

                    continue;
                }

                $pending = $pendingLessons[$day.'-'.$slot] ?? null;
                if ($pending) {
                    Lesson::updateOrCreate($where, collect($pending)->except(['day', 'slot'])->all());
                }
            }

            Announcement::create([
                'user_id' => $user->id,
                'title' => "Timetable updated for {$schoolClass->name}",
                'body' => 'The DOS updated the lesson times and timetable. Please check your lessons.',
                'audience' => $schoolClass->name,
            ]);
        });

        Audit::log('Timetable updated', $schoolClass->name);

        return redirect("/timetable?class={$schoolClass->id}")
            ->with('ok', 'Timetable, period times and lesson teachers saved. Trainers, students and parents see the updates.');
    }

    /**
     * @param  Collection<int, SchoolClass>  $classes
     * @return Collection<int, int>
     */
    private function trainerIds(Collection $classes): Collection
    {
        $classIds = $classes->pluck('id');

        return Module::whereIn('school_class_id', $classIds)
            ->whereNotNull('trainer_id')
            ->pluck('trainer_id')
            ->merge($classes->pluck('trainer_id')->filter())
            ->merge(Lesson::whereIn('school_class_id', $classIds)->whereNotNull('trainer_id')->pluck('trainer_id'))
            ->unique()
            ->values();
    }
}
