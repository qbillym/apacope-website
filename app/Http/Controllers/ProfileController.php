<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        return view('account', ['user' => $request->user()]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $values = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update(['password' => $values['password']]);
        $request->session()->regenerate();
        Audit::log('Account password changed');

        return back()->with('ok', 'Your password has been updated.');
    }
}
