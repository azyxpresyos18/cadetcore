<?php

namespace App\Http\Controllers;

use App\Models\{AttendanceRecord, Battalion, BattalionS1Assignment, Company, CorpsAdjutantAssignment, CorpsS1Assignment,
    Enrollment, MeritDemeritEntry, Platoon, PlatoonLeaderAssignment, TrainingSession, UserAccount};
use App\Support\{Ctx, Rows, Scope};
use Illuminate\Http\Request;

/** Read-only pages shared by Corps Adjutant, Corps S1 and Battalion S1 (battalion S1 sees only their battalion). */
class StaffController extends Controller
{
    public function dashboard(Request $r)
    {
        $s = Scope::for($r->user());
        $enr = $s->enrollments()->get();
        $ids = $enr->pluck('enrollment_id');
        $year = $s->year;

        $perBn = $enr->groupBy(fn ($e) => $e->platoon->company->battalion->battalion_name)->map->count()->sortDesc();
        $max = max(1, (int) $perBn->max());

        $recent = MeritDemeritEntry::with(['enrollment.cadet', 'enrollment.platoon.company.battalion', 'loggedBy.cadet'])
            ->whereIn('enrollment_id', $ids)->orderByDesc('entry_date')->orderByDesc('entry_id')->limit(8)->get()
            ->map(fn ($m) => array_slice(Rows::staffEntry($m), 0, 8))->all();

        $week = null;
        if ($year && today()->between($year->start_date, $year->end_date)) {
            $week = intdiv(today()->timestamp - \Carbon\Carbon::parse($year->start_date)->startOfDay()->timestamp, 7 * 86400) + 1;
        }

        return view('adjutant.dashboard', [
            'role'           => $s->role,
            'yearLabel'      => $year?->year_label,
            'week'           => $week,
            'totalCadets'    => $enr->count(),
            'officerCount'   => count($this->officerRows($s->yearId())),
            'battalionCount' => $perBn->count(),
            'recent'         => $recent,
            'bn'             => $perBn->map(fn ($n, $name) => [$name, (int) round($n / $max * 100), $n.' '.\Illuminate\Support\Str::plural('cadet', $n)])->values()->all(),
            'next'           => Ctx::nextSessionRow(Ctx::nextSession($s->yearId())),
        ]);
    }

    public function merits(Request $r)
    {
        $s = Scope::for($r->user());
        $ids = $s->enrollments()->pluck('enrollment_id');

        $entries = MeritDemeritEntry::with(['enrollment.cadet', 'enrollment.platoon.company.battalion', 'loggedBy.cadet'])
            ->whereIn('enrollment_id', $ids)->orderByDesc('entry_date')->orderByDesc('entry_id')->get()
            ->map(fn ($m) => Rows::staffEntry($m))->all();

        return view('adjutant.merits', ['role' => $s->role, 'yearLabel' => $s->year?->year_label, 'entries' => $entries]);
    }

    public function cadets(Request $r)
    {
        $s = Scope::for($r->user());
        $enr = $s->enrollments()->orderBy('enrollment_id')->get();
        $totals = Ctx::totals($enr->pluck('enrollment_id'));

        $rows = $enr->map(fn ($e) => Rows::cadet($e, (int) ($totals[$e->enrollment_id]->net ?? 0)))
            ->sortBy(fn ($row) => $row[1])->values()->all();

        return view('adjutant.cadets', [
            'role'         => $s->role,
            'yearLabel'    => $s->year?->year_label,
            'totalCadets'  => $enr->count(),
            'officerCount' => count($this->officerRows($s->yearId())),
            'activeCount'  => $enr->where('enrollment_status', 'active')->count(),
            'cadets'       => $rows,
            'structure'    => Ctx::structure(),
            'cadetTypes'   => config('cadetcore.cadet_types'),
            'battalions'   => Battalion::orderBy('battalion_name')->pluck('battalion_name')->unique()->values()->all(),
            'companies'    => Company::orderBy('company_name')->pluck('company_name')->unique()->values()->all(),
            'platoons'     => Platoon::orderBy('platoon_name')->pluck('platoon_name')->unique()->values()->all(),
            'statuses'     => collect($rows)->map(fn ($x) => $x[8])->unique()->values()->all(),
        ]);
    }

    public function attendance(Request $r)
    {
        $s = Scope::for($r->user());

        $sessions = TrainingSession::where('academic_year_id', $s->yearId())
            ->orderByDesc('session_date')->orderByDesc('start_time')->get();

        $selected = $sessions->firstWhere('session_id', (int) $r->query('session'))?->session_id ?? $sessions->first()?->session_id;

        $enrIds = $s->enrollments()->pluck('enrollment_id');
        $records = $selected
            ? AttendanceRecord::with(['enrollment.cadet', 'enrollment.platoon.company.battalion'])
                ->where('session_id', $selected)->whereIn('enrollment_id', $enrIds)->get()
                ->sortBy(fn ($a) => Ctx::name($a->enrollment->cadet))
            : collect();

        $tally = ['Present' => 0, 'Late' => 0, 'Excused' => 0, 'Absent' => 0];
        foreach ($records as $a) {
            if (isset($tally[$a->attendance_status])) $tally[$a->attendance_status]++;
        }

        return view('adjutant.attendance', [
            'role'            => $s->role,
            'sessions'        => $sessions->map(fn ($x) => [$x->session_id, $x->title.' · '.Ctx::date($x->session_date)])->all(),
            'selectedSession' => $selected,
            'tally'           => $tally,
            'battalions'      => Battalion::orderBy('battalion_name')->pluck('battalion_name')->all(),
            'rows'            => $records->map(fn ($a) => [
                $a->enrollment->cadet->serial_no,
                Ctx::name($a->enrollment->cadet),
                $a->enrollment->platoon->company->battalion->battalion_name,
                $a->enrollment->platoon->platoon_name,
                $a->attendance_status,
                Ctx::time($a->time_in),
                $a->remarks ?: '-',
            ])->values()->all(),
        ]);
    }

    public function reports(Request $r)
    {
        $s = Scope::for($r->user());
        $enr = $s->enrollments()->get();
        $ids = $enr->pluck('enrollment_id');
        $totals = Ctx::totals($ids);

        // Battalion comparison: cadets, merit entries, demerit entries.
        $bns = $enr->groupBy(fn ($e) => $e->platoon->company->battalion->battalion_name)->map(function ($group, $name) use ($totals) {
            $m = $group->sum(fn ($e) => (int) ($totals[$e->enrollment_id]->merit_n ?? 0));
            $d = $group->sum(fn ($e) => (int) ($totals[$e->enrollment_id]->demerit_n ?? 0));
            return [$name, $group->count(), $m, $d];
        })->sortKeys()->values()->all();

        // Top / bottom by net points.
        $people = $enr->map(fn ($e) => [
            Ctx::name($e->cadet),
            $e->platoon->company->battalion->battalion_name.' · '.$e->platoon->platoon_name,
            (int) ($totals[$e->enrollment_id]->net ?? 0),
        ]);
        $top = $people->filter(fn ($p) => $p[2] > 0)->sortByDesc(fn ($p) => $p[2])->take(5)->map(fn ($p) => [$p[0], $p[1], Ctx::net($p[2])])->values()->all();
        $low = $people->filter(fn ($p) => $p[2] < 0)->sortBy(fn ($p) => $p[2])->take(5)->map(fn ($p) => [$p[0], $p[1], Ctx::net($p[2])])->values()->all();

        // Attendance: Present + Late over everything logged this year.
        $att = AttendanceRecord::whereIn('enrollment_id', $ids)->selectRaw("COUNT(*) AS total, SUM(CASE WHEN attendance_status IN ('Present','Late') THEN 1 ELSE 0 END) AS ok")->first();
        $pct = ($att && $att->total > 0) ? round($att->ok / $att->total * 100).'%' : 'No data';

        return view('adjutant.reports', [
            'role'           => $s->role,
            'yearLabel'      => $s->year?->year_label,
            'unitStrength'   => $enr->count(),
            'battalionCount' => count($bns),
            'attendancePct'  => $pct,
            'bns'            => $bns,
            'top'            => $top,
            'low'            => $low,
            'battalions'     => collect($bns)->map(fn ($x) => $x[0])->all(),
        ]);
    }

    /** Officers page (Corps Adjutant and Corps S1 only; the route is limited to those roles). */
    public function officers(Request $r)
    {
        $s = Scope::for($r->user());

        return view('adjutant.officers', [
            'role'      => $s->role,
            'yearLabel' => $s->year?->year_label,
            'officers'  => $this->officerRows($s->yearId()),
            'structure' => Ctx::structure(),
        ]);
    }

    /** [serial, name, position, assigned unit, course / year, account status, cadet id, role, edit data] for everyone holding a position this year. */
    private function officerRows(?int $yearId): array
    {
        $rows = [];
        $push = function (string $role, string $position, string $unit, int $userId, array $where = []) use (&$rows, $yearId) {
            $u = UserAccount::with('cadet')->find($userId);
            if (! $u) return;
            $enr = Enrollment::where('academic_year_id', $yearId)->where('cadet_id', $u->cadet_id)->first();
            $c = $u->cadet;
            $rows[] = [
                $c->serial_no,
                Ctx::name($c),
                $position,
                $unit,
                trim(($c->course ?? '').($enr ? ' - Year '.$enr->year_level : ''), ' -'),
                $u->is_active ? 'Active' : 'Inactive',
                $c->cadet_id,
                $role,
                [   // prefill for the Edit dialog
                    'last' => $c->last_name, 'first' => $c->first_name, 'middle' => $c->middle_name, 'serial' => $c->serial_no,
                    'email' => $u->email, 'course' => trim(($c->course ?? '').($enr ? '-'.$enr->year_level : ''), '-'),
                    'pos' => ['corps_s1' => 'Corps S1', 'battalion_s1' => 'Battalion S1', 'platoon_leader' => 'Platoon Leader'][$role] ?? '',
                ] + $where,
            ];
        };

        foreach (CorpsAdjutantAssignment::where('academic_year_id', $yearId)->get() as $a) {
            $push('adjutant', 'Corps Adjutant', 'NA', $a->user_id);
        }
        foreach (CorpsS1Assignment::where('academic_year_id', $yearId)->get() as $a) {
            $push('corps_s1', 'Corps S1 Personnel Officer', 'NA', $a->user_id);
        }
        foreach (BattalionS1Assignment::where('academic_year_id', $yearId)->get() as $a) {
            $bn = Battalion::find($a->battalion_id)?->battalion_name ?? '-';
            $push('battalion_s1', 'Battalion S1 Officer', $bn, $a->user_id, ['bn' => $bn]);
        }
        foreach (PlatoonLeaderAssignment::where('academic_year_id', $yearId)->get() as $a) {
            $p = Platoon::with('company.battalion')->find($a->platoon_id);
            $push('platoon_leader', 'Platoon Leader', $p ? $p->company->battalion->battalion_name.' · '.$p->company->company_name.' · '.$p->platoon_name : '-', $a->user_id,
                $p ? ['bn' => $p->company->battalion->battalion_name, 'co' => $p->company->company_name, 'pl' => $p->platoon_name] : []);
        }

        return $rows;
    }
}
