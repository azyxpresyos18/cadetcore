<?php

namespace App\Http\Controllers;

use App\Models\{Cadet, CadetType, Enrollment, Platoon, Rank};
use App\Support\{Ctx, Rows, Scope};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Add / remove cadets (Corps Adjutant and Corps S1 only; routes are limited to those roles). */
class CadetRecordController extends Controller
{
    public function store(Request $r)
    {
        $s = Scope::for($r->user());
        abort_unless($s->year, 422, 'There is no current academic year.');

        $v = $r->validate([
            'cadet_type'  => ['required', Rule::in(config('cadetcore.cadet_types'))],
            'last_name'   => 'required|string|max:100',
            'first_name'  => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'serial'   => ['required', 'string', 'max:40', 'regex:'.config('cadetcore.serial_pattern')],
            'battalion'=> 'required|string',
            'company'  => 'required|string',
            'platoon'  => 'required|string',
            'course'   => 'required|string|max:150',
        ], ['serial.regex' => 'Serial number format must look like CE-R25-002444.']);

        $cy = Ctx::parseCourseYear($v['course']);
        if (! $cy) {
            throw ValidationException::withMessages(['course' => 'Write the course and year like this: Bachelor of Science in Information Systems-1']);
        }
        if (Cadet::where('serial_no', $v['serial'])->exists()) {
            throw ValidationException::withMessages(['serial' => 'Duplicate serial number: this cadet already exists.']);
        }

        $platoon = $this->findPlatoon($v['battalion'], $v['company'], $v['platoon']);
        if (! $platoon) {
            throw ValidationException::withMessages(['platoon' => 'That battalion, company and platoon combination does not exist.']);
        }

        $enrollment = DB::transaction(function () use ($s, $v, $cy, $platoon) {
            $cadet = Cadet::create([
                'serial_no' => $v['serial'], 'last_name' => trim($v['last_name']), 'first_name' => trim($v['first_name']),
                'middle_name' => filled($v['middle_name'] ?? null) ? trim($v['middle_name']) : null, 'course' => $cy[0],
            ]);
            return Enrollment::create([
                'academic_year_id'  => $s->yearId(),
                'platoon_id'        => $platoon->platoon_id,
                'rank_id'           => Rank::orderBy('rank_id')->value('rank_id'),
                'cadet_type_id'     => CadetType::firstOrCreate(['type_name' => $v['cadet_type']])->cadet_type_id,
                'cadet_id'          => $cadet->cadet_id,
                'year_level'        => $cy[1],
                'enrollment_status' => 'active',
            ]);
        });

        $enrollment->load(['cadet', 'platoon.company.battalion', 'rank', 'cadetType']);

        return response()->json(['message' => 'Cadet '.Ctx::name($enrollment->cadet).' was added successfully.', 'row' => Rows::cadet($enrollment)], 201);
    }

    /** Edit a cadet of the current academic year (names, serial number, cadet type, unit, course and year level). */
    public function update(Request $r, Enrollment $enrollment)
    {
        $s = Scope::for($r->user());
        abort_unless($enrollment->academic_year_id === $s->yearId(), 403, 'Records from an archived academic year cannot be changed.');
        $cadet = $enrollment->cadet;

        $v = $r->validate([
            'cadet_type'  => ['required', Rule::in(config('cadetcore.cadet_types'))],
            'last_name'   => 'required|string|max:100',
            'first_name'  => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'serial'      => ['required', 'string', 'max:40', 'regex:'.config('cadetcore.serial_pattern')],
            'battalion'   => 'required|string',
            'company'     => 'required|string',
            'platoon'     => 'required|string',
            'course'      => 'required|string|max:150',
        ], ['serial.regex' => 'Serial number format must look like CE-R25-002444.']);

        $cy = Ctx::parseCourseYear($v['course'])
            ?? throw ValidationException::withMessages(['course' => 'Write the course and year like this: Bachelor of Science in Information Systems-1']);
        if (Cadet::where('serial_no', $v['serial'])->where('cadet_id', '!=', $cadet->cadet_id)->exists()) {
            throw ValidationException::withMessages(['serial' => 'Duplicate serial number: another person already uses it.']);
        }
        $platoon = $this->findPlatoon($v['battalion'], $v['company'], $v['platoon'])
            ?? throw ValidationException::withMessages(['platoon' => 'That battalion, company and platoon combination does not exist.']);

        DB::transaction(function () use ($v, $cy, $cadet, $enrollment, $platoon) {
            $cadet->update([
                'serial_no' => $v['serial'], 'last_name' => trim($v['last_name']), 'first_name' => trim($v['first_name']),
                'middle_name' => filled($v['middle_name'] ?? null) ? trim($v['middle_name']) : null, 'course' => $cy[0],
            ]);
            // The serial number is also the cadet's username, so keep the two in step.
            \App\Models\UserAccount::where('cadet_id', $cadet->cadet_id)->update(['username' => $v['serial']]);
            $enrollment->update([
                'platoon_id' => $platoon->platoon_id, 'year_level' => $cy[1],
                'cadet_type_id' => CadetType::firstOrCreate(['type_name' => $v['cadet_type']])->cadet_type_id,
            ]);
        });

        return response()->json(['message' => 'Cadet '.Ctx::name($cadet->fresh()).' was updated.']);
    }

    /** "Remove" never deletes: the enrollment is flagged removed so attendance and merit history stay intact. */
    public function destroy(Request $r, Enrollment $enrollment)
    {
        $s = Scope::for($r->user());
        abort_unless($enrollment->academic_year_id === $s->yearId(), 403, 'Records from an archived academic year cannot be changed.');

        $enrollment->update(['enrollment_status' => 'removed']);

        return response()->json(['ok' => true]);
    }

    private function findPlatoon(string $battalion, string $company, string $platoon): ?Platoon
    {
        return Platoon::where('platoon_name', $platoon)
            ->whereHas('company', fn ($c) => $c->where('company_name', $company)
                ->whereHas('battalion', fn ($b) => $b->where('battalion_name', $battalion)))
            ->first();
    }
}
