<?php

use App\Http\Middleware\EnsureRole;
use App\Models\Cadet;
use App\Models\UserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', fn () => view('auth.login'))->name('login');

// Real login: checks the username/serial number and password in user_accounts.
Route::post('/login', function (Request $r) {
    $r->validate(['role' => 'required', 'login' => 'required', 'password' => 'required']);

    $user = UserAccount::where('username', $r->login)
        ->orWhere('email', $r->login)
        ->orWhereHas('cadet', fn ($q) => $q->where('serial_no', $r->login))
        ->first();

    if (! $user || ! $user->is_active || ! Hash::check($r->password, $user->password_hash)) {
        return back()->withInput($r->only('role', 'login'))->withErrors(['login' => 'Wrong username or password.']);
    }

    if ($user->role() !== $r->role) {
        return back()->withInput($r->only('role', 'login'))->withErrors(['role' => 'This account is not registered under the role you selected.']);
    }

    Auth::login($user);
    $r->session()->regenerate();

    return redirect()->route(match ($user->role()) {
        'adjutant'       => 'adjutant.dashboard',
        'corps_s1'       => 'corps.dashboard',
        'battalion_s1'   => 'battalion.dashboard',
        'platoon_leader' => 'platoon.dashboard',
        default          => 'cadet.attendance',
    });
})->name('login.submit');
// Sign up: activates an account for a cadet/officer who is already in the records.
Route::post('/signup', function (Request $r) {
    $r->validate([
        'serial' => 'required',
        'full_name' => 'required',
        'email' => 'required|email|unique:user_accounts,email',
        'password' => 'required|min:8|confirmed',
    ]);

    $cadet = Cadet::where('serial_no', $r->serial)->first();

    if (! $cadet) {
        return back()->withInput($r->except('password', 'password_confirmation'))
            ->withErrors(['serial' => 'Serial number not found. Please ask your officer to add you first.']);
    }

    if (! str_contains(strtolower($r->full_name), strtolower($cadet->last_name))) {
        return back()->withInput($r->except('password', 'password_confirmation'))
            ->withErrors(['full_name' => 'The name does not match the record for this serial number.']);
    }

    if (UserAccount::where('cadet_id', $cadet->cadet_id)->exists()) {
        return back()->withInput($r->except('password', 'password_confirmation'))
            ->withErrors(['serial' => 'This serial number already has an account.']);
    }

    UserAccount::create([
        'cadet_id' => $cadet->cadet_id,
        'username' => $cadet->serial_no,
        'email' => $r->email,
        'password_hash' => Hash::make($r->password),
        'is_active' => true,
    ]);

    return redirect()->route('login')->with(['status' => 'Account created. You can now sign in.', 'tab' => 'signin']);
})->name('signup.submit');
Route::post('/logout', function (Request $r) {
    Auth::logout();
    $r->session()->invalidate();
    $r->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

// Corps Adjutant, Corps S1 and Battalion S1 share the same page set (permissions differ inside the views).
foreach ([['adjutant', 'adjutant'], ['corps', 'corps_s1'], ['battalion', 'battalion_s1']] as [$prefix, $role]) {
    Route::prefix($prefix)->name("$prefix.")->middleware(['auth', EnsureRole::class . ":$role"])->group(function () use ($prefix, $role) {
        Route::view('/dashboard', 'adjutant.dashboard', compact('role'))->name('dashboard');
        Route::view('/training-schedule', 'adjutant.schedule', compact('role'))->name('schedule');
        Route::view('/merit-demerit', 'adjutant.merits', compact('role'))->name('merits');
        Route::view('/cadet-records', 'adjutant.cadets', compact('role'))->name('cadets');
        Route::view('/attendance', 'adjutant.attendance', compact('role'))->name('attendance');
        Route::view('/reports', 'adjutant.reports', compact('role'))->name('reports');
        if ($prefix === 'adjutant') {
            Route::view('/yearly-update', 'adjutant.yearly', compact('role'))->name('yearly');
            Route::view('/academic-years', 'adjutant.years', compact('role'))->name('years');
        }
    });
}

Route::prefix('platoon')->name('platoon.')->middleware(['auth', EnsureRole::class . ':platoon_leader'])->group(function () {
    $role = 'platoon_leader';
    Route::view('/dashboard', 'platoon.dashboard', compact('role'))->name('dashboard');
    Route::view('/training-schedule', 'adjutant.schedule', compact('role'))->name('schedule');
    Route::view('/attendance', 'platoon.attendance', compact('role'))->name('attendance');
    Route::view('/merit-demerit', 'platoon.merits', compact('role'))->name('merits');
});

Route::prefix('cadet')->name('cadet.')->middleware(['auth', EnsureRole::class . ':cadet'])->group(function () {
    Route::view('/attendance', 'cadet.attendance')->name('attendance');
    Route::view('/training-schedule', 'cadet.schedule')->name('schedule');
    Route::view('/merit-demerit', 'cadet.merits')->name('merits');
});
