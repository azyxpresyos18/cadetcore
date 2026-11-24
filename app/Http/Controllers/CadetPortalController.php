<?php

namespace App\Http\Controllers;

use App\Models\{AttendanceRecord, MeritDemeritEntry};
use App\Support\{Ctx, Scope};
use Illuminate\Http\Request;

/** A cadet's own view-only pages. */
class CadetPortalController extends Controller
{
    public function attendance(Request $r)
    {
        $s = Scope::for($r->user());
        $id = $s->enrollment?->enrollment_id;

        $records = AttendanceRecord::with('session')->where('enrollment_id', $id)->get()
            ->sortByDesc(fn ($a) => $a->session->session_date.' '.$a->session->start_time)->values();

        $total = $records->count();
        $tally = [];
        foreach (['present' => 'Present', 'late' => 'Late', 'excused' => 'Excused', 'absent' => 'Absent'] as $key => $status) {
            $n = $records->where('attendance_status', $status)->count();
            $tally[$key] = [$n, $total ? (int) round($n / $total * 100) : 0];
        }

        return view('cadet.attendance', [
            'tally' => $tally,
            'rows'  => $records->map(fn ($a) => [
                Ctx::date($a->session->session_date), $a->session->title, $a->attendance_status, Ctx::time($a->time_in), $a->remarks ?: '-',
            ])->all(),
        ]);
    }

    public function merits(Request $r)
    {
        $s = Scope::for($r->user());
        $entries = MeritDemeritEntry::with('loggedBy.cadet')->where('enrollment_id', $s->enrollment?->enrollment_id)
            ->orderByDesc('entry_date')->orderByDesc('entry_id')->get();

        return view('cadet.merits', [
            'merit'   => (int) $entries->where('entry_type', 'Merit')->sum('points'),
            'demerit' => (int) $entries->where('entry_type', 'Demerit')->sum('points'),
            'rows'    => $entries->map(fn ($m) => [
                Ctx::entryId($m->entry_id), Ctx::date($m->entry_date), $m->entry_type, Ctx::signed($m->entry_type, $m->points), $m->reason, Ctx::loggedBy($m),
            ])->all(),
        ]);
    }
}
