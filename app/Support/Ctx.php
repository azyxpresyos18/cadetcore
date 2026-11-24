<?php

namespace App\Support;

use App\Models\{AcademicYear, Battalion, Cadet, CadetType, Company, MeritDemeritEntry, Platoon, TrainingSession};
use Carbon\Carbon;

/** Small shared helpers: current year, name/date formatting, and parsing the roster formats the forms use. */
class Ctx
{
    private static AcademicYear|null|false $year = false;

    public static function year(): ?AcademicYear
    {
        if (self::$year === false) {
            self::$year = AcademicYear::where('is_current', true)->first();
        }
        return self::$year;
    }

    public static function resetYear(): void
    {
        self::$year = false;
    }

    /** "Surname, First M." */
    public static function name(?Cadet $c): string
    {
        if (! $c) return '-';
        $mid = $c->middle_name ? ' '.mb_substr($c->middle_name, 0, 1).'.' : '';
        return "{$c->last_name}, {$c->first_name}{$mid}";
    }

    /** Parses "Surname, First Name M." into [last, first, middle]. Returns null if it is not in that format. */
    public static function parseName(string $full): ?array
    {
        if (! str_contains($full, ',')) return null;
        [$last, $rest] = array_map('trim', explode(',', $full, 2));
        $middle = null;
        if (preg_match('/^(.*\S)\s+([A-Za-z])\.?$/u', $rest, $m)) {
            $rest = $m[1];
            $middle = strtoupper($m[2]);
        }
        return ($last === '' || $rest === '') ? null : [$last, $rest, $middle];
    }

    /** "BSIT-1" -> ['BSIT', 1] */
    public static function parseCourseYear(string $v): ?array
    {
        if (! preg_match('/^(.+?)\s*-\s*(\d)$/u', trim($v), $m)) return null;
        $course = preg_replace('/\s+/', ' ', trim($m[1]));
        return $course === '' ? null : [$course, (int) $m[2]];
    }

    /** Battalion => Company => [platoons], used by the cascading dropdowns (only combinations that exist). */
    public static function structure(): array
    {
        $companies = Company::orderBy('company_id')->get()->groupBy('battalion_id');
        $platoons  = Platoon::orderBy('platoon_id')->get()->groupBy('company_id');

        $tree = [];
        foreach (Battalion::orderBy('battalion_id')->get() as $b) {
            $tree[$b->battalion_name] = [];
            foreach ($companies->get($b->battalion_id, collect()) as $c) {
                $tree[$b->battalion_name][$c->company_name] = $platoons->get($c->company_id, collect())->pluck('platoon_name')->values()->all();
            }
        }
        return $tree;
    }

    public static function entryId(int $id): string
    {
        return 'MD-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    public static function signed(string $type, int $points): string
    {
        return ($type === 'Merit' ? '+' : '-').$points;
    }

    public static function net(int $n): string
    {
        return $n >= 0 ? '+'.$n : (string) $n;
    }

    public static function date(?string $d): string
    {
        return $d ? Carbon::parse($d)->format('d M Y') : '-';
    }

    public static function time(?string $t): string
    {
        return $t ? str_replace(':', '', substr($t, 0, 5)).'H' : '-';
    }

    /** [label, badge css class] from net points and enrollment status. */
    public static function standing(int $net, string $status): array
    {
        if ($status !== 'active') return [ucfirst($status), 'b-neutral'];
        $t = config('cadetcore.standing');
        if ($net >= $t['good_min']) return ['Good Standing', 'b-good'];
        if ($net > $t['probation_below']) return ['Warning', 'b-warning'];
        return ['Probation', 'b-probation'];
    }

    /** Next academic year label: 2026-2027 -> 2027-2028. */
    public static function nextLabel(?string $label): string
    {
        if ($label && preg_match('/^(\d{4})\D+(\d{4})$/', $label, $m)) {
            return ($m[1] + 1).'-'.($m[2] + 1);
        }
        return 'next academic year';
    }

    /** "Logged by" text for a merit/demerit entry or attendance record. */
    public static function loggedBy($row): string
    {
        $who = self::name($row->loggedBy?->cadet);
        return $row->substitute_officer ? "{$row->substitute_officer} (substitute for {$who})" : $who;
    }

    /** One training session shaped for the calendar partial. */
    public static function sessionRow(TrainingSession $s, int $typeCount): array
    {
        $types = $s->relationLoaded('audiences') ? $s->audiences : $s->audiences()->get();
        $audience = ($typeCount > 0 && $types->count() >= $typeCount) ? 'All Cadets'
            : ($types->pluck('type_name')->implode(', ') ?: 'All Cadets');

        return [
            'id'       => $s->session_id,
            'date'     => substr((string) $s->session_date, 0, 10),
            'title'    => $s->title,
            'time'     => self::time($s->start_time).' - '.self::time($s->end_time),
            'location' => $s->venue,
            'audience' => $audience,
            'type'     => $s->session_type ?? '',
        ];
    }

    /** Dropdown labels for the calendar's audience select. */
    public static function audienceOptions(): array
    {
        return array_merge(['All Cadets'], CadetType::orderBy('cadet_type_id')->pluck('type_name')->all());
    }

    /** Net/merit/demerit totals per enrollment id. */
    public static function totals($enrollmentIds): \Illuminate\Support\Collection
    {
        return MeritDemeritEntry::whereIn('enrollment_id', $enrollmentIds)
            ->selectRaw("enrollment_id,
                SUM(CASE WHEN entry_type = 'Merit' THEN points ELSE -points END) AS net,
                SUM(CASE WHEN entry_type = 'Merit' THEN points ELSE 0 END) AS merit_pts,
                SUM(CASE WHEN entry_type = 'Demerit' THEN points ELSE 0 END) AS demerit_pts,
                SUM(CASE WHEN entry_type = 'Merit' THEN 1 ELSE 0 END) AS merit_n,
                SUM(CASE WHEN entry_type = 'Demerit' THEN 1 ELSE 0 END) AS demerit_n")
            ->groupBy('enrollment_id')->get()->keyBy('enrollment_id');
    }

    public static function nextSession(?int $yearId)
    {
        return TrainingSession::where('academic_year_id', $yearId)
            ->whereDate('session_date', '>=', today())
            ->orderBy('session_date')->orderBy('start_time')->first();
    }

    public static function nextSessionRow($s): ?array
    {
        return $s ? [
            'title' => $s->title,
            'date'  => self::date($s->session_date),
            'time'  => self::time($s->start_time).' - '.self::time($s->end_time),
            'venue' => $s->venue,
        ] : null;
    }
}
