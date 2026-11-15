<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ForgotController extends Controller
{
    public function form(): View
    {
        return view('forgot');
    }

    public function store(Request $request): RedirectResponse
    {
        $values = $request->validate(['email' => ['required', 'email']]);

        if (config('mail.default') === 'log') {
            return back()->withErrors([
                'email' => 'Email delivery is not configured yet. Please contact the school administrator to reset your password.',
            ])->withInput();
        }

        try {
            $status = Password::sendResetLink($values);
        } catch (Throwable $exception) {
            Log::error('Could not send a password reset link.', [
                'email' => $values['email'],
                'exception' => $exception->getMessage(),
            ]);

            return back()->withErrors(['email' => 'We could not send the reset email. Please contact the school administrator.']);
        }

        if ($status === Password::RESET_LINK_SENT || $status === Password::RESET_THROTTLED) {
            return back()->with('ok', 'If that email belongs to an active account, a secure password reset link has been sent.');
        }

        return back()->with('ok', 'If that email belongs to an active account, a secure password reset link has been sent.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $values = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $status = Password::reset($values, function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            });
        } catch (Throwable $exception) {
            Log::error('Could not reset an account password.', [
                'email' => $values['email'],
                'exception' => $exception->getMessage(),
            ]);

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'We could not update your password. Please request a new reset link.',
            ]);
        }

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return redirect('/login')->with('ok', 'Your password has been changed. Sign in with your new password.');
    }
}
