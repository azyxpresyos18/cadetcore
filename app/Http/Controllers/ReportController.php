<?php

namespace App\Http\Controllers;

use App\Models\{AttendanceRecord, Battalion, MeritDemeritEntry};
use App\Support\{Ctx, Scope};
use Illuminate\Http\Request;

/**
 * Report export. "Excel" downloads a CSV (opens in Excel, no extra package needed);
 * "PDF" opens a clean printable page that the browser saves as PDF.
 */
class ReportController extends Controller
{
    private const TYPES = ['Battalion strength', 'Merit / demerit analysis', 'Attendance summary', 'Cadet entry history'];

    public function export(Request $r)
    {
        $v = $r->validate([
            'type'      => 'required|in:'.implode(',', self::TYPES),
            'battalion' => 'nullable|string',
            'from'      => 'required|date',
            'to'        => 'required|date|after_or_equal:from',
            'format'    => 'required|in:PDF,Excel',
        ], ['to.after_or_equal' => 'The end date must be after the start date.']);

        $s = Scope::for($r->user());
        $enr = $s->enrollments()->get();

        $bn = $v['battalion'] ?? '';
        if ($bn !== '' && $bn !== 'All battalions') {
            $enr = $enr->filter(fn ($e) => $e->platoon->company->battalion->battalion_name === $bn)->values();
            $scopeLabel = $bn;
        } else {
            $scopeLabel = 'All battalions';
        }

        [$headers, $rows] = match ($v['type']) {
            'Battalion strength'       => $this->strength($enr),
            'Merit / demerit analysis' => $this->merits($enr, $v['from'], $v['to']),
            'Attendance summary'       => $this->attendance($enr, $v['from'], $v['to']),
            'Cadet entry history'      => $this->history($enr, $v['from'], $v['to']),
        };

        $subtitle = $scopeLabel.' · '.Ctx::date($v['from']).' to '.Ctx::date($v['to']).' · AY '.($s->year?->year_label ?? '-');
        $file = str($v['type'])->slug().'-'.now()->format('Ymd-His');

        if ($v['format'] === 'Excel') {
            return response()->streamDownload(function () use ($headers, $rows, $v, $subtitle) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads names correctly
                fputcsv($out, [$v['type']]);
                fputcsv($out, [$subtitle]);
                fputcsv($out, []);
                fputcsv($out, $headers);
                foreach ($rows as $row) fputcsv($out, $row);
                fclose($out);
            }, $file.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('reports.print', ['title' => $v['type'], 'subtitle' => $subtitle, 'headers' => $headers, 'rows' => $rows]);
    }

    private function strength($enr): array
    {
        $rows = $enr->groupBy(fn ($e) => $e->platoon_id)->map(function ($g) {
            $p = $g->first()->platoon;
            return [$p->company->battalion->battalion_name, $p->company->company_name, $p->platoon_name, $g->count()];
        })->sortBy(fn ($x) => $x[0].$x[1].$x[2])->values()->all();
        $rows[] = ['TOTAL', '', '', $enr->count()];

        return [['Battalion', 'Company', 'Platoon', 'Cadets'], $rows];
    }

    private function entries($enr, string $from, string $to)
    {
        return MeritDemeritEntry::with(['enrollment.cadet', 'enrollment.platoon.company.battalion', 'loggedBy.cadet'])
            ->whereIn('enrollment_id', $enr->pluck('enrollment_id'))
            ->whereBetween('entry_date', [$from, $to])
            ->orderBy('entry_date')->orderBy('entry_id')->get();
    }

    private function merits($enr, string $from, string $to): array
    {
        $by = $this->entries($enr, $from, $to)->groupBy('enrollment_id');
        $rows = $enr->map(function ($e) use ($by) {
            $g = $by->get($e->enrollment_id, collect());
            $m = $g->where('entry_type', 'Merit');
            $d = $g->where('entry_type', 'Demerit');
            return [
                $e->cadet->serial_no, Ctx::name($e->cadet), $e->platoon->company->battalion->battalion_name, $e->platoon->platoon_name,
                $m->count(), $m->sum('points'), $d->count(), $d->sum('points'), $m->sum('points') - $d->sum('points'),
            ];
        })->sortBy(fn ($x) => $x[1])->values()->all();

        return [['Serial No.', 'Cadet', 'Battalion', 'Platoon', 'Merit entries', 'Merit points', 'Demerit entries', 'Demerit points', 'Net points'], $rows];
    }

    private function attendance($enr, string $from, string $to): array
    {
        $by = AttendanceRecord::with('session')
            ->whereIn('enrollment_id', $enr->pluck('enrollment_id'))
            ->whereHas('session', fn ($q) => $q->whereBetween('session_date', [$from, $to]))
            ->get()->groupBy('enrollment_id');

        $rows = $enr->map(function ($e) use ($by) {
            $g = $by->get($e->enrollment_id, collect());
            $c = fn ($st) => $g->where('attendance_status', $st)->count();
            $rate = $g->count() ? round(($c('Present') + $c('Late')) / $g->count() * 100).'%' : 'No data';
            return [$e->cadet->serial_no, Ctx::name($e->cadet), $e->platoon->company->battalion->battalion_name, $e->platoon->platoon_name,
                $c('Present'), $c('Late'), $c('Excused'), $c('Absent'), $rate];
        })->sortBy(fn ($x) => $x[1])->values()->all();

        return [['Serial No.', 'Cadet', 'Battalion', 'Platoon', 'Present', 'Late', 'Excused', 'Absent', 'Attendance rate'], $rows];
    }

    private function history($enr, string $from, string $to): array
    {
        $rows = $this->entries($enr, $from, $to)->map(fn ($m) => [
            Ctx::entryId($m->entry_id), Ctx::date($m->entry_date), $m->enrollment->cadet->serial_no, Ctx::name($m->enrollment->cadet),
            $m->enrollment->platoon->company->battalion->battalion_name, $m->entry_type, Ctx::signed($m->entry_type, $m->points),
            $m->reason, Ctx::loggedBy($m),
        ])->values()->all();

        return [['Entry ID', 'Date', 'Serial No.', 'Cadet', 'Battalion', 'Type', 'Points', 'Reason', 'Logged by'], $rows];
    }
}
