<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Choose a new password · GS APACOPE</title>
    <link rel="stylesheet" href="{{ asset('css/apacope.css') }}">
</head>
<body class="pub">
<div class="lg">
    <div class="lgl">
        <img src="{{ asset('images/logo.png') }}" alt="Groupe Scolaire APACOPE">
        <h1>Choose a new password</h1>
        <p>Use a new password you have not used for this account before.</p>
    </div>
    <div class="lgr">
        <form method="post" action="{{ route('password.update') }}">
            @csrf
            <h2>Reset password</h2>
            @if($errors->any())<p class="err">{{ $errors->first() }}</p>@endif
            <input type="hidden" name="token" value="{{ $token }}">
            <label for="email">Account email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required>
            <label for="password">New password</label>
            <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
            <label for="password-confirmation">Confirm new password</label>
            <input id="password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
            <button class="btn">Save new password</button>
            <p style="margin-top:16px"><a href="/login" class="mu">← Back to sign in</a></p>
        </form>
    </div>
</div>
</body>
</html>
