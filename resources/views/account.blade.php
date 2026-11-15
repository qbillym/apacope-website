@extends('layouts.app')
@section('t', 'Account settings')
@section('c')
<div class="card">
    <h3>Account details</h3>
    <p><b>{{ $user->name }}</b> · {{ $user->email }}</p>
    <p class="mu">{{ $user->label() }} · Change your password here at any time.</p>
</div>

<form method="post" action="/account/password" class="card" style="max-width:640px">
    @csrf
    <h3>Change password</h3>
    <p class="mu">Choose a password with at least 8 characters. Your current password is required to confirm this change.</p>
    <div class="toolbar">
        <div><label for="current-password">Current password</label><input id="current-password" type="password" name="current_password" autocomplete="current-password" required></div>
        <div><label for="new-password">New password</label><input id="new-password" type="password" name="password" autocomplete="new-password" minlength="8" required></div>
        <div><label for="confirm-password">Confirm new password</label><input id="confirm-password" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required></div>
    </div>
    <button class="btn" style="margin-top:18px">Update password</button>
</form>
@endsection
