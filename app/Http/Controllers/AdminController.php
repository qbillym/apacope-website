<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\PasswordRequest;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class AdminController extends Controller
{
    private function ok($r)
    {
        abort_unless($r->user()->role === 'admin', 403);
    }

    public function index(Request $r)
    {
        $this->ok($r);

        return view('admin', ['requests' => PasswordRequest::where('status', 'pending')->latest()->get(), 'classes' => SchoolClass::with('trainer')->orderBy('id')->get(), 'trainers' => User::where('role', 'trainer')->orderBy('name')->get(), 'year' => Setting::val('academic_year', date('Y')), 'term' => Setting::val('term', 'Term 2'), 'users' => User::orderBy('role')->orderBy('name')->get(), 'logs' => Audit::with('user')->latest()->take(40)->get()]);
    }

    public function store(Request $r)
    {
        $this->ok($r);
        $v = $r->validate(['name' => 'required|max:100', 'email' => 'required|email|unique:users', 'role' => 'required|in:admin,head,dosb,dost,trainer,student,parent,accountant,storekeeper']);
        User::create($v + ['password' => Str::random(48)]);
        Audit::log('Account created', $v['email']);

        return $this->sendResetLink($v['email'], 'Account created. A password setup link was sent to the user.');
    }

    public function toggle(Request $r, User $user)
    {
        $this->ok($r);
        abort_if($user->id === $r->user()->id, 403);
        $user->update(['active' => ! $user->active]);
        Audit::log($user->active ? 'Account enabled' : 'Account disabled', $user->email);

        return back()->with('ok', 'Account updated.');
    }

    public function reset(Request $r, User $user)
    {
        $this->ok($r);
        Audit::log('Password reset link requested', $user->email);

        return $this->sendResetLink($user->email, 'A secure password reset link was sent to '.$user->email.'.');
    }

    public function addClass(Request $r)
    {
        $this->ok($r);
        $v = $r->validate(['name' => 'required|max:30|unique:school_classes,name', 'section' => 'required|in:nursery,primary,olevel,tvet', 'trade' => 'nullable|max:60', 'level' => 'nullable|max:20']);
        SchoolClass::create($v);
        Audit::log('Class added', $v['name']);

        return back()->with('ok', 'Class added. The DOS can now set its timetable.');
    }

    public function assign(Request $r)
    {
        $this->ok($r);
        $v = $r->validate(['class_id' => 'required|exists:school_classes,id', 'trainer_id' => 'nullable|exists:users,id']);
        $c = SchoolClass::find($v['class_id']);
        $c->update(['trainer_id' => $v['trainer_id'] ?: null]);
        Audit::log('Trainer assigned', $c->name);

        return back()->with('ok', 'Saved.');
    }

    public function term(Request $r)
    {
        $this->ok($r);
        $v = $r->validate(['year' => 'required|max:20', 'term' => 'required|max:30']);
        Setting::updateOrCreate(['key' => 'academic_year'], ['value' => $v['year']]);
        Setting::updateOrCreate(['key' => 'term'], ['value' => $v['term']]);
        Audit::log('Academic term set', $v['term'].' '.$v['year']);

        return back()->with('ok', 'Saved.');
    }

    public function resetRequest(Request $r, PasswordRequest $passwordRequest)
    {
        $this->ok($r);
        $email = $passwordRequest->email;
        $passwordRequest->update(['status' => 'done']);
        Audit::log('Password reset link requested', $email);

        return $this->sendResetLink($email, 'A secure password reset link was sent to '.$email.'.');
    }

    private function sendResetLink(string $email, string $successMessage): RedirectResponse
    {
        if (config('mail.default') === 'log') {
            return back()->with('err', 'Email delivery is not configured. Set up SMTP or another mail provider before sending password links.');
        }try {
            $status = Password::sendResetLink(['email' => $email]);
        } catch (Throwable $exception) {
            Log::error('Could not send an account password reset link.', ['email' => $email, 'exception' => $exception->getMessage()]);

            return back()->with('err', 'The account action was saved, but the password email could not be sent. Check the mail settings.');
        }if ($status !== Password::RESET_LINK_SENT) {
            return back()->with('err','The password email was not sent. Check the account email and mail settings.');
        }

return back()->with('ok',$successMessage);
    }
}
