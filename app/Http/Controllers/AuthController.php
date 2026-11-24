<?php

namespace App\Http\Controllers;

use App\Models\{Cadet, Enrollment, UserAccount};
use App\Support\Ctx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash};

class AuthController extends Controller
{
    public function login(Request $r)
    {
        $r->validate(['role' => 'required', 'login' => 'required', 'password' => 'required']);

        $user = UserAccount::where('username', $r->login)
            ->orWhere('email', $r->login)
            ->orWhereHas('cadet', fn ($q) => $q->where('serial_no', $r->login))
            ->first();

        if (! $user || ! $user->is_active || ! Hash::check($r->password, $user->password_hash)) {
            return back()->withInput($r->only('role', 'login'))->withErrors(['login' => 'Wrong username or password.']);
        }

        $role = $user->role();
        if ($role !== $r->role) {
            return back()->withInput($r->only('role', 'login'))->withErrors(['role' => 'This account is not registered under the role you selected.']);
        }

        Auth::login($user);
        $r->session()->regenerate();
        $r->session()->put('display_name', $this->displayName($user));

        return redirect()->route(match ($role) {
            'adjutant'       => 'adjutant.dashboard',
            'corps_s1'       => 'corps.dashboard',
            'battalion_s1'   => 'battalion.dashboard',
            'platoon_leader' => 'platoon.dashboard',
            default          => 'cadet.attendance',
        });
    }

    public function signup(Request $r)
    {
        $r->validate([
            'serial'    => 'required',
            'full_name' => 'required',
            'email'     => 'required|email|unique:user_accounts,email',
            'password'  => 'required|min:8|confirmed',
        ]);

        $keep = $r->except('password', 'password_confirmation');
        $cadet = Cadet::where('serial_no', $r->serial)->first();

        if (! $cadet) {
            return back()->withInput($keep)->withErrors(['serial' => 'Serial number not found. Please ask your officer to add you first.']);
        }
        if (! str_contains(strtolower($r->full_name), strtolower($cadet->last_name))) {
            return back()->withInput($keep)->withErrors(['full_name' => 'The name does not match the record for this serial number.']);
        }
        if (UserAccount::where('cadet_id', $cadet->cadet_id)->exists()) {
            return back()->withInput($keep)->withErrors(['serial' => 'This serial number already has an account.']);
        }

        UserAccount::create([
            'cadet_id'      => $cadet->cadet_id,
            'username'      => $cadet->serial_no,
            'email'         => $r->email,
            'password_hash' => Hash::make($r->password),
            'is_active'     => true,
        ]);

        return redirect()->route('login')->with(['status' => 'Account created. You can now sign in.', 'tab' => 'signin']);
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('login');
    }

    /** "Santos, Maria" - shown in the sidebar and stamped on attendance / merit pages. */
    private function displayName(UserAccount $user): string
    {
        // The role (Cadet / officer position) is already shown under the name, so only the name is shown here.
        return Ctx::name($user->cadet);
    }
}
