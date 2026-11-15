<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $this->authorizeDos($user);
        $classes = $user->classes()->with('trainer')->orderBy('name')->get();
        $classIds = $classes->pluck('id');
        $modules = Module::with(['schoolClass', 'trainer'])
            ->whereIn('school_class_id', $classIds)
            ->orderBy('school_class_id')
            ->orderBy('name')
            ->get();
        $trainerIds = $this->trainerIds($classes);
        $visibleTrainers = User::with(['modules.schoolClass'])
            ->where('role', 'trainer')
            ->whereIn('id', $trainerIds)
            ->orderBy('name')
            ->get();

        return view('teachers', [
            'classes' => $classes,
            'modules' => $modules,
            'visibleTrainers' => $visibleTrainers,
            'trainers' => User::with(['modules.schoolClass'])
                ->where('role', 'trainer')
                ->where('active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeDos($user);
        $classIds = $user->classes()->pluck('id');

        $values = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'module_id' => ['required', 'integer', Rule::exists('modules', 'id')->whereIn('school_class_id', $classIds)],
            'class_teacher' => ['nullable', 'boolean'],
        ]);

        $module = Module::whereIn('school_class_id', $classIds)->findOrFail($values['module_id']);
        $temporaryPassword = Str::random(12);

        DB::transaction(function () use ($values, $module, $temporaryPassword): void {
            $trainer = User::create([
                'name' => $values['name'],
                'email' => $values['email'],
                'password' => $temporaryPassword,
                'role' => 'trainer',
                'active' => true,
            ]);
            $module->update(['trainer_id' => $trainer->id]);

            if (filter_var($values['class_teacher'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                SchoolClass::whereKey($module->school_class_id)->update(['trainer_id' => $trainer->id]);
            }
        });

        Audit::log('Trainer account created', $values['email'].' · '.$module->schoolClass->name.' · '.$module->name);
        if (config('mail.default') === 'log') {
            return back()->with('ok', 'Trainer account created. Temporary password: '.$temporaryPassword.' (Email delivery is not configured, so share this password with the trainer directly)');
        }

        try {
            $status = Password::sendResetLink(['email' => $values['email']]);
        } catch (Throwable $exception) {
            Log::error('Could not send the new trainer password setup link.', [
                'email' => $values['email'],
                'exception' => $exception->getMessage(),
            ]);

            return back()->with('err', 'Trainer account was created, but the password setup email could not be sent. Check the mail settings, then use IT administration to send a reset link.');
        }

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->with('err', 'Trainer account was created, but the password setup email was not sent. Check the email address and mail settings.');
        }

        return back()->with('ok', 'Trainer account created. A password setup link was sent to their email address.');
    }

    public function update(Request $request, User $trainer): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeDos($user);
        abort_unless($trainer->role === 'trainer' && $user->classes()->whereIn(
            'id',
            $trainer->classes()->select('school_classes.id')
        )->exists(), 404);

        $values = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($trainer->id)],
        ]);
        $trainer->update($values);
        Audit::log('Trainer account updated', $trainer->email);

        return back()->with('ok', 'Trainer account updated.');
    }

    public function assignModule(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeDos($user);
        $classes = $user->classes()->get();
        $classIds = $classes->pluck('id');
        $values = $request->validate([
            'module_id' => ['required', 'integer', Rule::exists('modules', 'id')->whereIn('school_class_id', $classIds)],
            'trainer_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'trainer')],
        ]);

        $module = Module::whereIn('school_class_id', $classIds)->findOrFail($values['module_id']);
        $trainer = empty($values['trainer_id'])
            ? null
            : User::where('role', 'trainer')->where('active', true)->findOrFail($values['trainer_id']);

        DB::transaction(function () use ($module, $trainer): void {
            $module->update(['trainer_id' => $trainer?->id]);
            Lesson::where('school_class_id', $module->school_class_id)
                ->where('subject', $module->name)
                ->update(['trainer_id' => $trainer?->id]);
        });

        Audit::log('Module trainer assigned', $module->schoolClass->name.' · '.$module->name.' · '.($trainer?->name ?? 'Unassigned'));

        return back()->with('ok', 'Module teacher and matching timetable lessons updated.');
    }

    public function assignClassTeacher(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeDos($user);
        $classIds = $user->classes()->pluck('id');
        $values = $request->validate([
            'class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')->whereIn('id', $classIds)],
            'trainer_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'trainer')->where('active', true)],
        ]);

        $trainerId = $values['trainer_id'] ?? null;

        SchoolClass::whereIn('id', $classIds)
            ->whereKey($values['class_id'])
            ->update(['trainer_id' => $trainerId]);
        Audit::log('Class teacher assigned', SchoolClass::findOrFail($values['class_id'])->name.' · '.($trainerId ? User::find($trainerId)->name : 'Unassigned'));

        return back()->with('ok', 'Class teacher assignment saved.');
    }

    private function authorizeDos(User $user): void
    {
        abort_unless(in_array($user->role, ['dosb', 'dost'], true), 403);
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
