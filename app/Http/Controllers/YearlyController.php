<?php

namespace App\Http\Controllers;

use App\Models\{AcademicYear, AttendanceRecord, Battalion, BattalionS1Assignment, Cadet, CadetType, Company, CorpsAdjutantAssignment,
    CorpsS1Assignment, Enrollment, Officer, Platoon, PlatoonLeaderAssignment, Rank, UserAccount};
use App\Support\{Ctx, Scope};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Yearly rollover (Corps Adjutant only) and the read-only archive of past academic years. */
class YearlyController extends Controller
{
    /** Position label used by the "Add officer account" dialog -> internal role key. */
    private const POSITIONS = [
        'Corps Adjutant' => 'adjutant', 'Corps S1' => 'corps_s1', 'Battalion S1' => 'battalion_s1', 'Platoon Leader' => 'platoon_leader',
    ];
    private const LABELS = ['adjutant' => 'Corps Adjutant', 'corps_s1' => 'Corps S1', 'battalion_s1' => 'Battalion S1', 'platoon_leader' => 'Platoon Leader'];

    public function show(Request $r)
    {
        $s = Scope::for($r->user());
        $final = config('cadetcore.final_year');

        $groups = Enrollment::with('cadetType')->where('academic_year_id', $s->yearId())->where('enrollment_status', 'active')
            ->where('year_level', '>=', $final)->get()->groupBy('cadet_type_id')
            ->map(fn ($g) => [
                'key'   => $g->first()->cadet_type_id,
                'n'     => $g->count(),
                'label' => 'graduating '.$g->first()->cadetType->type_name.' records (year level '.$final.')',
            ])->values()->all();

        return view('adjutant.yearly', [
            'role'       => $s->role,
            'yearLabel'  => $s->year?->year_label,
            'officers'   => array_map(fn ($o) => [$o['name'], $o['serial'], $o['position'], $o['unit'], $o['key']], $this->officerRows($s->yearId())),
            'groups'     => $groups,
            'groupTotal' => array_sum(array_column($groups, 'n')),
            'nextLabel'  => Ctx::nextLabel($s->year?->year_label),
            'structure'  => Ctx::structure(),
            'battalions' => Battalion::orderBy('battalion_name')->pluck('battalion_name')->unique()->values()->all(),
            'companies'  => Company::orderBy('company_name')->pluck('company_name')->unique()->values()->all(),
            'platoons'   => Platoon::orderBy('platoon_name')->pluck('platoon_name')->unique()->values()->all(),
        ]);
    }

    public function years(Request $r)
    {
        $out = [];
        foreach (AcademicYear::where('is_current', false)->orderByDesc('start_date')->get() as $y) {
            $enr = Enrollment::with(['cadet', 'platoon.company.battalion'])->where('academic_year_id', $y->academic_year_id)
                ->where('enrollment_status', '!=', 'removed')->get();
            $ids = $enr->pluck('enrollment_id');
            $totals = Ctx::totals($ids);
            $att = AttendanceRecord::whereIn('enrollment_id', $ids)
                ->selectRaw("enrollment_id, COUNT(*) AS total, SUM(CASE WHEN attendance_status IN ('Present','Late') THEN 1 ELSE 0 END) AS ok")
                ->groupBy('enrollment_id')->get()->keyBy('enrollment_id');
            $allTotal = $att->sum('total');

            $out[$y->year_label] = [
                'cadets'    => $enr->count(),
                'officers'  => count($this->officerRows($y->academic_year_id)),
                'att'       => $allTotal ? (int) round($att->sum('ok') / $allTotal * 100) : 0,
                'merits'    => (int) $totals->sum('merit_n'),
                'demerits'  => (int) $totals->sum('demerit_n'),
                'archived'  => $y->end_date,
                'records'   => $enr->sortBy(fn ($e) => Ctx::name($e->cadet))->map(function ($e) use ($totals, $att) {
                    $a = $att->get($e->enrollment_id);
                    return [
                        $e->cadet->serial_no, Ctx::name($e->cadet), $e->platoon->company->battalion->battalion_name,
                        ucfirst($e->enrollment_status), Ctx::net((int) ($totals[$e->enrollment_id]->net ?? 0)),
                        ($a && $a->total) ? round($a->ok / $a->total * 100).'%' : '-',
                    ];
                })->values()->all(),
            ];
        }

        return view('adjutant.years', ['role' => 'adjutant', 'years' => $out]);
    }

    /** The whole rollover in one transaction: either everything happens or nothing does. */
    public function confirm(Request $r)
    {
        $v = $r->validate([
            'archive'               => 'nullable|array',
            'archive.*'             => 'integer',
            'cadets'                => 'nullable|array',
            'cadets.*.sn'           => 'required|string',
            'cadets.*.name'         => 'required|string',
            'cadets.*.bn'           => 'required|string',
            'cadets.*.co'           => 'required|string',
            'cadets.*.pl'           => 'required|string',
            'cadets.*.cy'           => 'required|string',
            'officers'              => 'nullable|array',
            'officers.*.key'        => 'required|string',
            'officers.*.action'     => 'required|in:Keep,Replace,Deactivate',
            'officers.*.name'       => 'nullable|string',
            'officers.*.sn'         => 'nullable|string',
            'newOfficers'           => 'nullable|array',
            'newOfficers.*.name'    => 'required|string',
            'newOfficers.*.sn'      => 'required|string',
            'newOfficers.*.pos'     => 'required|in:'.implode(',', array_keys(self::POSITIONS)),
            'newOfficers.*.bn'      => 'nullable|string',
            'newOfficers.*.co'      => 'nullable|string',
            'newOfficers.*.pl'      => 'nullable|string',
        ]);

        $temp = [];
        $label = DB::transaction(function () use ($v, &$temp) {
            return $this->rollover($v, $temp);
        });
        Ctx::resetYear();

        return response()->json(['message' => "AY {$label} has been prepared.", 'tempPasswords' => $temp]);
    }

    private function rollover(array $v, array &$temp): string
    {
        $cur = Ctx::year();
        if (! $cur) $this->fail('There is no current academic year to close.');
        $nextLabel = Ctx::nextLabel($cur->year_label);
        if (! preg_match('/^\d{4}-\d{4}$/', $nextLabel)) $this->fail('The current year label must look like 2026-2027.');
        if (AcademicYear::where('year_label', $nextLabel)->exists()) $this->fail("AY {$nextLabel} already exists.");

        $next = AcademicYear::create([
            'year_label' => $nextLabel,
            'start_date' => Carbon::parse($cur->start_date)->addYear()->toDateString(),
            'end_date'   => Carbon::parse($cur->end_date)->addYear()->toDateString(),
            'is_current' => false,
        ]);
        $nid = $next->academic_year_id;
        $final = config('cadetcore.final_year');

        // 1. Archive graduating records, carry everyone else into the new year (year level + 1).
        $archiveTypes = array_map('intval', $v['archive'] ?? []);
        $graduated = [];
        foreach (Enrollment::where('academic_year_id', $cur->academic_year_id)->where('enrollment_status', 'active')->get() as $e) {
            if ($e->year_level >= $final && in_array((int) $e->cadet_type_id, $archiveTypes)) {
                $e->update(['enrollment_status' => 'graduated']);
                $graduated[] = $e->cadet_id;
            } else {
                Enrollment::create([
                    'academic_year_id' => $nid, 'platoon_id' => $e->platoon_id, 'rank_id' => $e->rank_id,
                    'cadet_type_id' => $e->cadet_type_id, 'cadet_id' => $e->cadet_id,
                    'year_level' => min($e->year_level + 1, $final), 'enrollment_status' => 'active',
                ]);
                $e->update(['enrollment_status' => 'completed']);
            }
        }
        if ($graduated) {
            UserAccount::whereIn('cadet_id', $graduated)->update(['is_active' => false]);
        }

        // 2. Staged new cadets.
        $seen = [];
        foreach ($v['cadets'] ?? [] as $c) {
            $sn = trim($c['sn']);
            if (! preg_match(config('cadetcore.serial_pattern'), $sn)) $this->fail("Serial number {$sn} must look like CE-R25-002444.");
            if (isset($seen[$sn]) || Cadet::where('serial_no', $sn)->exists()) $this->fail("Duplicate serial number: {$sn} already exists.");
            $seen[$sn] = true;

            $name = Ctx::parseName($c['name']) ?? $this->fail("Write {$c['name']} as \"Surname, First Name M.\"");
            $cy = Ctx::parseCourseYear($c['cy']) ?? $this->fail("Write the course and year for {$sn} like this: Bachelor of Science in Information Systems-1");
            $platoon = $this->platoon($c['bn'], $c['co'], $c['pl']) ?? $this->fail("{$sn}: that battalion, company and platoon combination does not exist.");

            $cadet = Cadet::create(['serial_no' => $sn, 'last_name' => $name[0], 'first_name' => $name[1], 'middle_name' => $name[2], 'course' => $cy[0]]);
            Enrollment::create([
                'academic_year_id' => $nid, 'platoon_id' => $platoon->platoon_id,
                'rank_id' => Rank::orderBy('rank_id')->value('rank_id'), 'cadet_type_id' => CadetType::orderBy('cadet_type_id')->value('cadet_type_id'),
                'cadet_id' => $cadet->cadet_id, 'year_level' => $cy[1], 'enrollment_status' => 'active',
            ]);
        }

        // 3. Officer positions for the new year. $slots maps position key => user id.
        $slots = [];
        $actions = collect($v['officers'] ?? [])->keyBy('key');
        foreach ($this->officerRows($cur->academic_year_id) as $o) {
            $a = $actions->get($o['key']);
            $action = $a['action'] ?? 'Keep';

            if ($action === 'Keep') {
                if (in_array($o['cadet_id'], $graduated)) {
                    $this->fail("{$o['name']} is graduating. Choose Replace or Deactivate for the {$o['position']} position.");
                }
                $slots[$o['key']] = $o['user_id'];
            } elseif ($action === 'Replace') {
                if (blank($a['name'] ?? null) || blank($a['sn'] ?? null)) $this->fail("Enter the new appointee for {$o['position']} ({$o['unit']}).");
                $slots[$o['key']] = $this->appointee($a['name'], trim($a['sn']), $o['role'], $this->platoonForSlot($o['role'], $o['unit_id']), $nid, $temp);
            } else {
                UserAccount::where('user_id', $o['user_id'])->update(['is_active' => false]);
            }
        }

        foreach ($v['newOfficers'] ?? [] as $n) {
            $role = self::POSITIONS[$n['pos']];
            $unitId = null;
            $platoon = null;
            if ($role === 'platoon_leader') {
                $platoon = $this->platoon($n['bn'] ?? '', $n['co'] ?? '', $n['pl'] ?? '') ?? $this->fail("{$n['name']}: that battalion, company and platoon combination does not exist.");
                $unitId = $platoon->platoon_id;
            } elseif ($role === 'battalion_s1') {
                $unitId = Battalion::where('battalion_name', $n['bn'] ?? '')->value('battalion_id') ?? $this->fail("{$n['name']}: choose a battalion.");
                $platoon = $this->platoonForSlot($role, $unitId);
            }

            $key = $this->slotKey($role, $unitId);
            if (isset($slots[$key])) {
                $this->fail("The {$n['pos']} position is already filled for that unit. Use Replace on the current officer instead.");
            }
            $slots[$key] = $this->appointee($n['name'], trim($n['sn']), $role, $platoon, $nid, $temp);
        }

        foreach ($slots as $key => $userId) {
            [$role, $unitId] = array_pad(explode(':', $key, 2), 2, null);
            match ($role) {
                'adjutant'       => CorpsAdjutantAssignment::create(['academic_year_id' => $nid, 'user_id' => $userId]),
                'corps_s1'       => CorpsS1Assignment::create(['academic_year_id' => $nid, 'user_id' => $userId]),
                'battalion_s1'   => BattalionS1Assignment::create(['battalion_id' => $unitId, 'academic_year_id' => $nid, 'user_id' => $userId]),
                'platoon_leader' => PlatoonLeaderAssignment::create(['platoon_id' => $unitId, 'academic_year_id' => $nid, 'user_id' => $userId]),
            };
        }

        // 4. Switch the active year. The old year stays in the database, read-only, for the archive page.
        $cur->update(['is_current' => false]);
        $next->update(['is_current' => true]);

        return $nextLabel;
    }

    /**
     * Finds or creates the cadet + officer record + login for an appointee and makes sure they are enrolled in the new year.
     * A brand-new account gets a temporary password that is returned once to the Adjutant.
     */
    private function appointee(string $name, string $sn, string $role, ?Platoon $platoon, int $nid, array &$temp): int
    {
        $cadet = Cadet::where('serial_no', $sn)->first();
        if (! $cadet) {
            if (! preg_match(config('cadetcore.serial_pattern'), $sn)) $this->fail("Serial number {$sn} must look like CE-R25-002444.");
            $parts = Ctx::parseName($name) ?? $this->fail("Write {$name} as \"Surname, First Name M.\"");
            $cadet = Cadet::create(['serial_no' => $sn, 'last_name' => $parts[0], 'first_name' => $parts[1], 'middle_name' => $parts[2]]);
        }

        Officer::firstOrCreate(['cadet_id' => $cadet->cadet_id], ['officer_serial_no' => $sn]);

        $account = UserAccount::where('cadet_id', $cadet->cadet_id)->first();
        if ($account) {
            $account->update(['is_active' => true]);
        } else {
            $pw = Str::password(10, symbols: false);
            $account = UserAccount::create(['cadet_id' => $cadet->cadet_id, 'username' => $sn, 'password_hash' => Hash::make($pw), 'is_active' => true]);
            $temp[] = ['serial' => $sn, 'name' => Ctx::name($cadet), 'password' => $pw];
        }

        $rankId = Rank::where('rank_name', config("cadetcore.officer_rank.{$role}"))->value('rank_id') ?? Rank::orderBy('rank_id')->value('rank_id');
        $typeId = CadetType::where('type_name', 'Officer')->value('cadet_type_id') ?? CadetType::orderBy('cadet_type_id')->value('cadet_type_id');

        $enr = Enrollment::where('academic_year_id', $nid)->where('cadet_id', $cadet->cadet_id)->first();
        if ($enr) {
            $enr->update(['rank_id' => $rankId, 'cadet_type_id' => $typeId]);
        } else {
            Enrollment::create([
                'academic_year_id' => $nid, 'cadet_id' => $cadet->cadet_id, 'rank_id' => $rankId, 'cadet_type_id' => $typeId,
                'platoon_id' => ($platoon ?? Platoon::orderBy('platoon_id')->first())?->platoon_id ?? $this->fail('Create a platoon before appointing officers.'),
                'year_level' => config('cadetcore.final_year'), 'enrollment_status' => 'active',
            ]);
        }

        return $account->user_id;
    }

    /** Everyone holding an officer position in the given year. */
    private function officerRows(?int $yearId): array
    {
        $rows = [];
        $push = function (string $role, ?int $unitId, string $unitLabel, int $userId) use (&$rows) {
            $u = UserAccount::with('cadet')->find($userId);
            $rows[] = [
                'key' => $this->slotKey($role, $unitId), 'role' => $role, 'position' => self::LABELS[$role], 'unit' => $unitLabel, 'unit_id' => $unitId,
                'user_id' => $userId, 'cadet_id' => $u?->cadet_id, 'name' => Ctx::name($u?->cadet), 'serial' => $u?->cadet?->serial_no ?? '-',
            ];
        };

        foreach (CorpsAdjutantAssignment::where('academic_year_id', $yearId)->get() as $a) $push('adjutant', null, 'Corps level', $a->user_id);
        foreach (CorpsS1Assignment::where('academic_year_id', $yearId)->get() as $a) $push('corps_s1', null, 'Corps level', $a->user_id);
        foreach (BattalionS1Assignment::where('academic_year_id', $yearId)->get() as $a) {
            $push('battalion_s1', $a->battalion_id, Battalion::find($a->battalion_id)?->battalion_name ?? '-', $a->user_id);
        }
        foreach (PlatoonLeaderAssignment::where('academic_year_id', $yearId)->get() as $a) {
            $p = Platoon::with('company.battalion')->find($a->platoon_id);
            $push('platoon_leader', $a->platoon_id, $p ? $p->company->battalion->battalion_name.' · '.$p->platoon_name : '-', $a->user_id);
        }

        return $rows;
    }

    private function slotKey(string $role, ?int $unitId): string
    {
        return $unitId ? "{$role}:{$unitId}" : $role;
    }

    /** Any platoon in the unit, used as the home platoon of an appointee who has no enrollment yet. */
    private function platoonForSlot(string $role, ?int $unitId): ?Platoon
    {
        return match ($role) {
            'platoon_leader' => Platoon::find($unitId),
            'battalion_s1'   => Platoon::whereHas('company', fn ($c) => $c->where('battalion_id', $unitId))->orderBy('platoon_id')->first(),
            default          => null,
        };
    }

    private function platoon(string $battalion, string $company, string $platoon): ?Platoon
    {
        return Platoon::where('platoon_name', $platoon)
            ->whereHas('company', fn ($c) => $c->where('company_name', $company)
                ->whereHas('battalion', fn ($b) => $b->where('battalion_name', $battalion)))
            ->first();
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['rollover' => $message]);
    }
}
