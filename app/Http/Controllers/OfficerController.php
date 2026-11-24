<?php

namespace App\Http\Controllers;

use App\Models\{Battalion, BattalionS1Assignment, Cadet, CadetType, CorpsAdjutantAssignment, CorpsS1Assignment, Enrollment,
    Officer, Platoon, PlatoonLeaderAssignment, Rank, UserAccount};
use App\Support\{Ctx, Scope};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Add / edit / remove officers (Corps Adjutant and Corps S1 only; the routes are limited to those roles). */
class OfficerController extends Controller
{
    /** Dropdown label => role key (same keys as UserAccount::role() and config('cadetcore.officer_rank')). */
    private const POSITIONS = ['Corps S1' => 'corps_s1', 'Battalion S1' => 'battalion_s1', 'Platoon Leader' => 'platoon_leader'];

    public function store(Request $r)
    {
        $s = Scope::for($r->user());
        abort_unless($s->year, 422, 'There is no current academic year.');

        $v = $this->validated($r);
        if (Cadet::where('serial_no', $v['serial'])->exists()) {
            throw ValidationException::withMessages(['serial' => 'Duplicate serial number: this person already exists.']);
        }
        [$role, $cy, $battalion, $platoon] = $this->resolve($v);
        $this->assertSlotFree($role, $s->yearId(), $battalion, $platoon, $v['position']);

        $password = Str::password(10, symbols: false);

        $cadet = DB::transaction(function () use ($v, $role, $cy, $s, $platoon, $battalion, $password) {
            $cadet = Cadet::create([
                'serial_no' => $v['serial'], 'last_name' => trim($v['last_name']), 'first_name' => trim($v['first_name']),
                'middle_name' => filled($v['middle_name'] ?? null) ? trim($v['middle_name']) : null, 'course' => $cy[0],
            ]);
            Officer::create(['cadet_id' => $cadet->cadet_id, 'officer_serial_no' => $v['serial']]);

            $user = UserAccount::create([
                'cadet_id' => $cadet->cadet_id, 'username' => $v['serial'], 'email' => filled($v['email'] ?? null) ? trim($v['email']) : null,
                'password_hash' => Hash::make($password), 'is_active' => true,
            ]);

            Enrollment::create([
                'academic_year_id' => $s->yearId(), 'platoon_id' => $platoon->platoon_id, 'rank_id' => $this->rankId($role),
                'cadet_type_id' => CadetType::firstOrCreate(['type_name' => 'Officer'])->cadet_type_id,
                'cadet_id' => $cadet->cadet_id, 'year_level' => $cy[1], 'enrollment_status' => 'active',
            ]);

            $this->assign($role, $s->yearId(), $user->user_id, $battalion, $platoon);
            return $cadet;
        });

        return response()->json([
            'message'  => 'Officer '.Ctx::name($cadet).' was added as '.$v['position'].'.',
            'serial'   => $cadet->serial_no,
            'password' => $password,
        ], 201);
    }

    public function update(Request $r, Cadet $cadet)
    {
        $s = Scope::for($r->user());
        [$user, $current] = $this->holder($cadet, $s);
        abort_if($current === 'adjutant', 403, 'The Corps Adjutant cannot be edited here.');

        $v = $this->validated($r, $user);
        if (Cadet::where('serial_no', $v['serial'])->where('cadet_id', '!=', $cadet->cadet_id)->exists()) {
            throw ValidationException::withMessages(['serial' => 'Duplicate serial number: another person already uses it.']);
        }
        [$role, $cy, $battalion, $platoon] = $this->resolve($v);
        $this->assertSlotFree($role, $s->yearId(), $battalion, $platoon, $v['position'], $user->user_id);

        DB::transaction(function () use ($v, $role, $cy, $s, $cadet, $user, $platoon, $battalion) {
            $cadet->update([
                'serial_no' => $v['serial'], 'last_name' => trim($v['last_name']), 'first_name' => trim($v['first_name']),
                'middle_name' => filled($v['middle_name'] ?? null) ? trim($v['middle_name']) : null, 'course' => $cy[0],
            ]);
            Officer::updateOrCreate(['cadet_id' => $cadet->cadet_id], ['officer_serial_no' => $v['serial']]);
            $user->update(['username' => $v['serial'], 'email' => filled($v['email'] ?? null) ? trim($v['email']) : null]);

            Enrollment::updateOrCreate(
                ['academic_year_id' => $s->yearId(), 'cadet_id' => $cadet->cadet_id],
                ['platoon_id' => $platoon->platoon_id, 'rank_id' => $this->rankId($role), 'year_level' => $cy[1], 'enrollment_status' => 'active',
                 'cadet_type_id' => CadetType::firstOrCreate(['type_name' => 'Officer'])->cadet_type_id],
            );

            $this->unassign($s->yearId(), $user->user_id);
            $this->assign($role, $s->yearId(), $user->user_id, $battalion, $platoon);
        });

        return response()->json(['message' => 'Officer '.Ctx::name($cadet->fresh()).' was updated.']);
    }

    /** Removes the position and switches the login off. The person's records and history stay in the database. */
    public function destroy(Request $r, Cadet $cadet)
    {
        $s = Scope::for($r->user());
        [$user, $current] = $this->holder($cadet, $s);
        abort_if($current === 'adjutant', 403, 'The Corps Adjutant cannot be removed.');
        abort_if((int) $user->user_id === (int) $r->user()->user_id, 403, 'You cannot remove your own account.');

        DB::transaction(function () use ($s, $user, $cadet) {
            $this->unassign($s->yearId(), $user->user_id);
            $user->update(['is_active' => false]);
            Enrollment::where('academic_year_id', $s->yearId())->where('cadet_id', $cadet->cadet_id)->update(['enrollment_status' => 'removed']);
        });

        return response()->json(['ok' => true]);
    }

    private function validated(Request $r, ?UserAccount $self = null): array
    {
        return $r->validate([
            'last_name'   => 'required|string|max:100',
            'first_name'  => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'serial'      => ['required', 'string', 'max:40', 'regex:'.config('cadetcore.serial_pattern')],
            'email'       => ['nullable', 'email', 'max:150', Rule::unique('user_accounts', 'email')->ignore($self?->user_id, 'user_id')],
            'position'    => ['required', Rule::in(array_keys(self::POSITIONS))],
            'battalion'   => 'nullable|string',
            'company'     => 'nullable|string',
            'platoon'     => 'nullable|string',
            'course'      => 'required|string|max:150',
        ], ['serial.regex' => 'Serial number format must look like CE-R25-002444.']);
    }

    /** @return array{0: string, 1: array, 2: ?Battalion, 3: Platoon} role, [course, year], battalion, home platoon */
    private function resolve(array $v): array
    {
        $role = self::POSITIONS[$v['position']];
        $cy = Ctx::parseCourseYear($v['course'])
            ?? throw ValidationException::withMessages(['course' => 'Write the course and year like this: Bachelor of Science in Information Systems-1']);

        $battalion = null;
        if ($role === 'battalion_s1') {
            $battalion = Battalion::where('battalion_name', $v['battalion'] ?? '')->first()
                ?? throw ValidationException::withMessages(['battalion' => 'Choose the battalion this officer is under.']);
            $platoon = Platoon::whereHas('company', fn ($c) => $c->where('battalion_id', $battalion->battalion_id))->orderBy('platoon_id')->first();
        } elseif ($role === 'platoon_leader') {
            $platoon = Platoon::where('platoon_name', $v['platoon'] ?? '')
                ->whereHas('company', fn ($c) => $c->where('company_name', $v['company'] ?? '')
                    ->whereHas('battalion', fn ($b) => $b->where('battalion_name', $v['battalion'] ?? '')))
                ->first()
                ?? throw ValidationException::withMessages(['platoon' => 'That battalion, company and platoon combination does not exist.']);
        } else {
            $platoon = Platoon::orderBy('platoon_id')->first();
        }
        if (! $platoon) {
            throw ValidationException::withMessages(['position' => 'Create the battalion, company and platoon structure first (php artisan cadetcore:structure).']);
        }

        return [$role, $cy, $battalion, $platoon];
    }

    /** Each position has one holder per academic year (and per unit). */
    private function assertSlotFree(string $role, int $yearId, ?Battalion $battalion, Platoon $platoon, string $label, ?int $exceptUserId = null): void
    {
        $q = match ($role) {
            'corps_s1'       => CorpsS1Assignment::where('academic_year_id', $yearId),
            'battalion_s1'   => BattalionS1Assignment::where('academic_year_id', $yearId)->where('battalion_id', $battalion->battalion_id),
            'platoon_leader' => PlatoonLeaderAssignment::where('academic_year_id', $yearId)->where('platoon_id', $platoon->platoon_id),
        };
        if ($exceptUserId) {
            $q->where('user_id', '!=', $exceptUserId);
        }
        if ($q->exists()) {
            throw ValidationException::withMessages(['position' => "The {$label} position is already filled for that unit this academic year."]);
        }
    }

    private function assign(string $role, int $yearId, int $userId, ?Battalion $battalion, Platoon $platoon): void
    {
        match ($role) {
            'corps_s1'       => CorpsS1Assignment::create(['academic_year_id' => $yearId, 'user_id' => $userId]),
            'battalion_s1'   => BattalionS1Assignment::create(['battalion_id' => $battalion->battalion_id, 'academic_year_id' => $yearId, 'user_id' => $userId]),
            'platoon_leader' => PlatoonLeaderAssignment::create(['platoon_id' => $platoon->platoon_id, 'academic_year_id' => $yearId, 'user_id' => $userId]),
        };
    }

    private function unassign(int $yearId, int $userId): void
    {
        foreach ([CorpsS1Assignment::class, BattalionS1Assignment::class, PlatoonLeaderAssignment::class] as $m) {
            $m::where('academic_year_id', $yearId)->where('user_id', $userId)->delete();
        }
    }

    /** The account and current position of an officer in this academic year; 404 when the person holds none. */
    private function holder(Cadet $cadet, Scope $s): array
    {
        abort_unless($s->year, 422, 'There is no current academic year.');
        $user = UserAccount::where('cadet_id', $cadet->cadet_id)->first();
        abort_unless($user, 404);
        $role = $user->role();
        abort_if($role === 'cadet', 404);

        return [$user, $role];
    }

    private function rankId(string $role): ?int
    {
        return Rank::where('rank_name', config("cadetcore.officer_rank.{$role}"))->value('rank_id') ?? Rank::orderBy('rank_id')->value('rank_id');
    }
}
