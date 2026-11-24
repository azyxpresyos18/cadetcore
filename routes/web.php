<?php

use App\Http\Controllers\{AuthController, CadetPortalController, CadetRecordController, OfficerController, PasswordResetController, PlatoonController, ReportController,
    ScheduleController, StaffController, YearlyController};
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', fn () => view('auth.login'))->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.submit');
Route::post('/signup', [AuthController::class, 'signup'])->middleware('throttle:10,1')->name('signup.submit');
Route::post('/forgot-password', [PasswordResetController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'form'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Corps Adjutant, Corps S1 and Battalion S1 share the same page set. What each one can see or change is decided
// in the controllers (Scope) and by which routes exist for the prefix below.
foreach ([['adjutant', 'adjutant'], ['corps', 'corps_s1'], ['battalion', 'battalion_s1']] as [$prefix, $role]) {
    Route::prefix($prefix)->name("$prefix.")->middleware(['auth', EnsureRole::class . ":$role"])->group(function () use ($prefix) {
        Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');
        Route::get('/merit-demerit', [StaffController::class, 'merits'])->name('merits');
        Route::get('/cadet-records', [StaffController::class, 'cadets'])->name('cadets');
        Route::get('/attendance', [StaffController::class, 'attendance'])->name('attendance');
        Route::get('/reports', [StaffController::class, 'reports'])->name('reports');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/training-schedule', [ScheduleController::class, 'show'])->name('schedule');

        // Only the Adjutant and Corps S1 can change the schedule and the cadet roster.
        if (in_array($prefix, ['adjutant', 'corps'], true)) {
            Route::get('/officers', [StaffController::class, 'officers'])->name('officers');
            Route::post('/officers', [OfficerController::class, 'store'])->name('officers.store');
            Route::put('/officers/{cadet}', [OfficerController::class, 'update'])->name('officers.update');
            Route::delete('/officers/{cadet}', [OfficerController::class, 'destroy'])->name('officers.destroy');
            Route::post('/training-schedule', [ScheduleController::class, 'store'])->name('sessions.store');
            Route::put('/training-schedule/{trainingSession}', [ScheduleController::class, 'update'])->name('sessions.update');
            Route::delete('/training-schedule/{trainingSession}', [ScheduleController::class, 'destroy'])->name('sessions.destroy');
            Route::post('/cadet-records', [CadetRecordController::class, 'store'])->name('cadets.store');
            Route::put('/cadet-records/{enrollment}', [CadetRecordController::class, 'update'])->name('cadets.update');
            Route::delete('/cadet-records/{enrollment}', [CadetRecordController::class, 'destroy'])->name('cadets.destroy');
        }

        if ($prefix === 'adjutant') {
            Route::get('/yearly-update', [YearlyController::class, 'show'])->name('yearly');
            Route::post('/yearly-update', [YearlyController::class, 'confirm'])->name('yearly.confirm');
            Route::get('/academic-years', [YearlyController::class, 'years'])->name('years');
        }
    });
}

Route::prefix('platoon')->name('platoon.')->middleware(['auth', EnsureRole::class . ':platoon_leader'])->group(function () {
    Route::get('/dashboard', [PlatoonController::class, 'dashboard'])->name('dashboard');
    Route::get('/training-schedule', [ScheduleController::class, 'show'])->name('schedule');
    Route::get('/attendance', [PlatoonController::class, 'attendance'])->name('attendance');
    Route::get('/attendance/{trainingSession}/records', [PlatoonController::class, 'attendanceRecords'])->name('attendance.records');
    Route::post('/attendance', [PlatoonController::class, 'saveAttendance'])->name('attendance.save');
    Route::get('/merit-demerit', [PlatoonController::class, 'merits'])->name('merits');
    Route::post('/merit-demerit', [PlatoonController::class, 'storeMerit'])->name('merits.store');
});

Route::prefix('cadet')->name('cadet.')->middleware(['auth', EnsureRole::class . ':cadet'])->group(function () {
    Route::get('/attendance', [CadetPortalController::class, 'attendance'])->name('attendance');
    Route::get('/training-schedule', [ScheduleController::class, 'show'])->name('schedule');
    Route::get('/merit-demerit', [CadetPortalController::class, 'merits'])->name('merits');
});
