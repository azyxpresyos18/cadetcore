<?php

namespace App\Support;

use App\Models\{Enrollment, MeritDemeritEntry};

/** Turns models into the plain row arrays the Blade views expect. */
class Rows
{
    /** Cadet Records row: indexes 0-9 are what the view prints, 10 is the enrollment id used by Remove. */
    public static function cadet(Enrollment $e, int $net = 0): array
    {
        $p = $e->platoon;
        [$label, $class] = Ctx::standing($net, $e->enrollment_status);
        $course = trim(($e->cadet->course ?? '').' - Year '.$e->year_level, ' -');

        return [
            $e->cadet->serial_no,
            Ctx::name($e->cadet),
            $p->company->battalion->battalion_name,
            $p->company->company_name,
            $p->platoon_name,
            $e->rank->rank_name,
            $course,
            Ctx::net($net),
            $label,
            $class,
            $e->enrollment_id,
            [   // prefill for the Edit dialog
                'type' => $e->cadetType?->type_name, 'last' => $e->cadet->last_name, 'first' => $e->cadet->first_name,
                'middle' => $e->cadet->middle_name, 'serial' => $e->cadet->serial_no,
                'bn' => $p->company->battalion->battalion_name, 'co' => $p->company->company_name, 'pl' => $p->platoon_name,
                'course' => trim(($e->cadet->course ?? '').'-'.$e->year_level, '-'),
            ],
        ];
    }

    /** Merit/demerit entry, staff layout: [id, date, cadet, battalion, type, signed pts, reason, logged by, audit]. */
    public static function staffEntry(MeritDemeritEntry $m): array
    {
        $logged = Ctx::loggedBy($m);
        return [
            Ctx::entryId($m->entry_id),
            Ctx::date($m->entry_date),
            Ctx::name($m->enrollment->cadet),
            $m->enrollment->platoon->company->battalion->battalion_name,
            $m->entry_type,
            Ctx::signed($m->entry_type, $m->points),
            $m->reason,
            $logged,
            'Entry '.Ctx::entryId($m->entry_id).' logged by '.$logged.' on '.Ctx::date($m->entry_date).'.',
        ];
    }

    /** Platoon layout: [id, date, cadet, type, unsigned pts, reason, logged by]. */
    public static function platoonEntry(MeritDemeritEntry $m): array
    {
        return [
            Ctx::entryId($m->entry_id),
            Ctx::date($m->entry_date),
            Ctx::name($m->enrollment->cadet),
            $m->entry_type,
            $m->points,
            $m->reason,
            Ctx::loggedBy($m),
        ];
    }
}
