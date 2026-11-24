<?php

namespace App\Http\Controllers;

use App\Models\UserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Log, Mail};
use Illuminate\Support\Str;

/** Forgot password: emails a one-hour link to the address saved on the account (cadets give it at Sign Up, officers at Add New Officer). */
class PasswordResetController extends Controller
{
    private const MINUTES = 60;

    public function send(Request $r)
    {
        $v = $r->validate(['email' => 'required|email']);
        $user = UserAccount::where('email', $v['email'])->where('is_active', true)->first();

        if ($user) {
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $v['email']],
                ['token' => hash('sha256', $token), 'created_at' => now()],
            );

            $link = route('password.reset', ['token' => $token, 'email' => $v['email']]);
            try {
                Mail::raw("Hello,\n\nWe received a request to reset your CadetCore password. Open this link to choose a new one (valid for ".self::MINUTES." minutes):\n\n{$link}\n\nIf you did not ask for this, you can ignore this email.", function ($m) use ($v) {
                    $m->to($v['email'])->subject('CadetCore password reset');
                });
            } catch (\Throwable $e) {
                Log::error('Password reset email failed: '.$e->getMessage());
            }
        }

        // Same answer whether or not the email exists, so the form cannot be used to find out who has an account.
        return response()->json(['ok' => true]);
    }

    public function form(Request $r, string $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => (string) $r->query('email')]);
    }

    public function reset(Request $r)
    {
        $v = $r->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $row = DB::table('password_reset_tokens')->where('email', $v['email'])->first();
        $valid = $row
            && hash_equals($row->token, hash('sha256', $v['token']))
            && now()->diffInMinutes($row->created_at, true) <= self::MINUTES;
        $user = $valid ? UserAccount::where('email', $v['email'])->where('is_active', true)->first() : null;

        if (! $user) {
            return back()->withInput($r->only('email'))->withErrors(['email' => 'This reset link is invalid or has expired. Please request a new one.']);
        }

        $user->update(['password_hash' => Hash::make($v['password'])]);
        DB::table('password_reset_tokens')->where('email', $v['email'])->delete();

        return redirect()->route('login')->with(['status' => 'Password changed. You can now sign in.', 'tab' => 'signin']);
    }
}
