<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CadetCore - Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Outfit:wght@400;600&family=Space+Mono&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/cadetcore.css') }}">
</head>
<body>
@php $tab = session('tab', old('_tab', 'signin')); @endphp
<div class="login-wrap">
  <div class="hero">
    <small>RESERVE OFFICERS' TRAINING CORPS</small>
    <h1>CADET<b>CORE</b></h1>
    <p>ROTC Training Schedule and Merit/Demerit Tracking System: centralized management of cadet attendance, training schedules, and discipline records across all battalions.</p>
  </div>
  <div class="login-card">
    <div class="logo-circle"><img src="{{ asset('images/logo.png') }}" alt="CadetCore" onerror="this.parentNode.textContent='CADETCORE'"></div>
    <div class="toggle">
      <button type="button" id="tabIn" class="{{ $tab === 'signin' ? 'on' : '' }}">Sign In</button>
      <button type="button" id="tabUp" class="{{ $tab === 'signup' ? 'on' : '' }}">Sign Up</button>
    </div>
    @if (session('status'))<div class="alert ok">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif

    <form id="formIn" class="{{ $tab === 'signin' ? '' : 'hide' }}" method="POST" action="{{ route('login.submit') }}">
      @csrf
      <h3>Sign in to your account</h3>
      <label class="f"><span>Role</span>
        <select name="role" required>
          <option value="">Select role...</option>
          @foreach (['corps_s1'=>'Corps S1 Personnel Officer','adjutant'=>'Corps Adjutant','battalion_s1'=>'Battalion S1 Officer','platoon_leader'=>'Platoon Leader','cadet'=>'Cadet'] as $v => $l)
            <option value="{{ $v }}" @selected(old('role') === $v)>{{ $l }}</option>
          @endforeach
        </select>
      </label>
      <label class="f"><span>Email / Serial number</span><input name="login" value="{{ old('login') }}" autocomplete="username" required></label>
      <label class="f"><span>Password</span><input type="password" name="password" required></label>
      <button class="btn" type="submit">LOGIN</button>
      <a href="#" id="forgotLink">Forgot password?</a>
    </form>

    <form id="formUp" class="{{ $tab === 'signup' ? '' : 'hide' }}" method="POST" action="{{ route('signup.submit') }}">
      @csrf
      <input type="hidden" name="_tab" value="signup">
      <h3>Activate your CadetCore Account</h3>
      <label class="f"><span>Cadet or Officer serial number</span><input name="serial" value="{{ old('serial') }}" required></label>
      <label class="f"><span>Full name</span><input name="full_name" value="{{ old('full_name') }}" required></label>
      <label class="f"><span>Email address</span><input type="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" required></label>
      <label class="f"><span>Create password</span><input type="password" name="password" minlength="8" placeholder="At least 8 characters" required></label>
      <label class="f"><span>Confirm password</span><input type="password" name="password_confirmation" placeholder="Enter the password again" required></label>
      <button class="btn" type="submit">CREATE ACCOUNT</button>
    </form>
  </div>
</div>
<script>
const show = up => {
  document.getElementById('formIn').classList.toggle('hide', up);
  document.getElementById('formUp').classList.toggle('hide', !up);
  document.getElementById('tabIn').classList.toggle('on', !up);
  document.getElementById('tabUp').classList.toggle('on', up);
};
document.getElementById('tabIn').onclick = () => show(false);
document.getElementById('tabUp').onclick = () => show(true);
</script>

<dialog id="forgotDlg">
  <div id="fpForm">
    <h3>Reset your password</h3>
    <div class="alert note">Enter the email address associated with your CadetCore account. We will send password reset instructions to the account's registered contact method.</div>
    <label class="f"><span>Email address</span><input type="email" id="fp_email" placeholder="name@example.com" autocomplete="email"></label>
    <div id="fp_err" class="alert err hide"></div>
    <div class="dlg-actions">
      <button type="button" class="btn ghost" style="color:var(--ink);border-color:var(--ink)" id="fpCancel">Cancel</button>
      <button type="button" class="btn" id="fpSend">Send reset instructions</button>
    </div>
  </div>
  <div id="fpDone" class="hide">
    <h3>Password reset request sent</h3>
    <div class="alert ok">If an account exists for <strong id="fp_shown"></strong>, reset instructions have been sent to its registered contact method.</div>
    <p style="margin:0 0 6px">Check your inbox and follow the link to set a new password. If you do not receive anything, contact your Corps S1 Personnel Officer.</p>
    <div class="dlg-actions"><button type="button" class="btn" id="fpClose">Back to sign in</button></div>
  </div>
</dialog>
<script>
const fp = id => document.getElementById(id);
const resetFp = () => { fp('fpForm').classList.remove('hide'); fp('fpDone').classList.add('hide'); fp('fp_email').value = ''; fp('fp_err').classList.add('hide'); };
fp('forgotLink').onclick = e => { e.preventDefault(); resetFp(); fp('forgotDlg').showModal(); };
fp('fpCancel').onclick = fp('fpClose').onclick = () => fp('forgotDlg').close();
fp('fpSend').onclick = async () => {
  const em = fp('fp_email');
  if (!em.value.trim() || !em.checkValidity()) { fp('fp_err').textContent = 'Please enter a valid email address.'; fp('fp_err').classList.remove('hide'); return; }
  fp('fp_err').classList.add('hide');
  fp('fpSend').disabled = true;
  try {
    const res = await fetch(@json(route('password.email')), {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())},
      body: JSON.stringify({email: em.value.trim()}),
    });
    if (!res.ok) throw new Error(res.status === 429 ? 'Too many requests. Please wait a minute and try again.' : 'Please enter a valid email address.');
    fp('fp_shown').textContent = em.value.trim();
    fp('fpForm').classList.add('hide'); fp('fpDone').classList.remove('hide');
  } catch (err) { fp('fp_err').textContent = err.message; fp('fp_err').classList.remove('hide'); }
  fp('fpSend').disabled = false;
};
</script>
</body>
</html>
