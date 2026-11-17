<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', fn () => view('auth.login'))->name('login');

// DEMO ONLY: no real authentication yet. Replace with Laravel auth + role middleware later.
Route::post('/login', function (Request $r) {
    $r->validate(['role' => 'required', 'login' => 'required', 'password' => 'required']);
    return match ($r->role) {
        'adjutant'       => redirect()->route('adjutant.dashboard'),
        'corps_s1'       => redirect()->route('corps.dashboard'),
        'battalion_s1'   => redirect()->route('battalion.dashboard'),
        'platoon_leader' => redirect()->route('platoon.dashboard'),
        'cadet'          => redirect()->route('cadet.attendance'),
        default          => back()->withInput()->withErrors(['role' => 'Please choose a role.']),
    };
})->name('login.submit');
// DEMO ONLY: replace with real account activation (check serial number against the cadet/officer records, then create the user).
Route::post('/signup', function (Request $r) {
    $r->validate([
        'serial' => 'required',
        'full_name' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);
    return redirect()->route('login')->with(['status' => 'Account created. You can now sign in.', 'tab' => 'signin']);
})->name('signup.submit');
Route::post('/logout', fn () => redirect()->route('login'))->name('logout');

// Corps Adjutant, Corps S1 and Battalion S1 share the same page set (permissions differ inside the views).
foreach ([['adjutant', 'adjutant'], ['corps', 'corps_s1'], ['battalion', 'battalion_s1']] as [$prefix, $role]) {
    Route::prefix($prefix)->name("$prefix.")->group(function () use ($prefix, $role) {
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

Route::prefix('platoon')->name('platoon.')->group(function () {
    $role = 'platoon_leader';
    Route::view('/dashboard', 'platoon.dashboard', compact('role'))->name('dashboard');
    Route::view('/training-schedule', 'adjutant.schedule', compact('role'))->name('schedule');
    Route::view('/attendance', 'platoon.attendance', compact('role'))->name('attendance');
    Route::view('/merit-demerit', 'platoon.merits', compact('role'))->name('merits');
});

Route::prefix('cadet')->name('cadet.')->group(function () {
    Route::view('/attendance', 'cadet.attendance')->name('attendance');
    Route::view('/training-schedule', 'cadet.schedule')->name('schedule');
    Route::view('/merit-demerit', 'cadet.merits')->name('merits');
});
