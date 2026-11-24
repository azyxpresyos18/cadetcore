<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CadetCore - Reset password</title>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Outfit:wght@400;600&family=Space+Mono&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/cadetcore.css') }}">
</head>
<body>
<div class="login-wrap">
  <div class="hero">
    <small>RESERVE OFFICERS' TRAINING CORPS</small>
    <h1>CADET<b>CORE</b></h1>
    <p>Choose a new password for your account.</p>
  </div>
  <div class="login-card">
    @if ($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.update') }}">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">
      <h3>Reset your password</h3>
      <label class="f"><span>Email address</span><input type="email" name="email" value="{{ old('email', $email) }}" required></label>
      <label class="f"><span>New password</span><input type="password" name="password" minlength="8" placeholder="At least 8 characters" required></label>
      <label class="f"><span>Confirm new password</span><input type="password" name="password_confirmation" placeholder="Enter the password again" required></label>
      <button class="btn" type="submit">CHANGE PASSWORD</button>
      <a href="{{ route('login') }}">Back to sign in</a>
    </form>
  </div>
</div>
</body>
</html>
