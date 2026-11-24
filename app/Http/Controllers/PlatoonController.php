<?php

namespace App\Http\Controllers;

use App\Models\{AttendanceRecord, MeritDemeritEntry, TrainingSession};
use App\Support\{Ctx, Rows, Scope};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Platoon Leader pages: dashboard, taking attendance and logging merits / demerits for their own platoon. */
class PlatoonController extends Controller
{
    public function dashboard(Request $r)
    {
        $s = Scope::for($r->user());
        $enr = $s->enrollments()->get();
        $ids = $enr->pluck('enrollment_id');
        $first = $enr->first()?->platoon;

        $mine = MeritDemeritEntry::with(['enrollment.cadet', 'loggedBy.cadet'])
            ->where('logged_by_user_id', $s->user->user_id)->whereIn('enrollment_id', $ids)
            ->orderByDesc('entry_date')->orderByDesc('entry_id');

        return view('platoon.dashboard', [
            'role'           => $s->role,
            'yearLabel'      => $s->year?->year_label,
            'unit'           => $first ? $first->company->battalion->battalion_name.' · '.$first->platoon_name : 'Platoon',
            'platoonName'    => $first?->platoon_name,
            'cadetCount'     => $enr->count(),
            'sessionsLogged' => AttendanceRecord::whereIn('enrollment_id', $ids)->distinct()->count('session_id'),
            'minePerType'    => (clone $mine)->count(),
            'mine'           => (clone $mine)->limit(8)->get()->map(fn ($m) => [
                Ctx::entryId($m->entry_id), Ctx::date($m->entry_date), Ctx::name($m->enrollment->cadet),
                $m->entry_type, Ctx::signed($m->entry_type, $m->points), $m->reason,
            ])->all(),
            'next'           => Ctx::nextSessionRow(Ctx::nextSession($s->yearId())),
        ]);
    }

    /* ---------- attendance ---------- */

    public function attendance(Request $r)
    {
        $s = Scope::for($r->user());

        return view('platoon.attendance', [
            'role'     => $s->role,
            'cadets'   => $s->enrollments()->get()->map(fn ($e) => [$e->cadet->serial_no, Ctx::name($e->cadet)])->sortBy(fn ($x) => $x[1])->values()->all(),
            'sessions' => TrainingSession::where('academic_year_id', $s->yearId())->orderByDesc('session_date')->orderByDesc('start_time')->get()
                ->map(fn ($x) => [$x->session_id, $x->title.' · '.Ctx::date($x->session_date)])->all(),
        ]);
    }

    /** Previously saved attendance for a session, so the form can be pre-filled instead of overwritten blindly. */
    public function attendanceRecords(Request $r, TrainingSession $trainingSession)
    {
        $s = Scope::for($r->user());
        abort_unless($trainingSession->academic_year_id === $s->yearId(), 403);

        $ids = $s->enrollments()->pluck('enrollment_id');
        $out = AttendanceRecord::with('enrollment.cadet')->where('session_id', $trainingSession->session_id)->whereIn('enrollment_id', $ids)->get()
            ->mapWithKeys(fn ($a) => [$a->enrollment->cadet->serial_no => [
                'status' => $a->attendance_status, 'time_in' => $a->time_in ? substr($a->time_in, 0, 5) : '', 'remark' => $a->remarks ?? '',
            ]]);

        return response()->json($out);
    }

    public function saveAttendance(Request $r)
    {
        $s = Scope::for($r->user());

        $v = $r->validate([
            'session_id'     => 'required|integer|exists:training_sessions,session_id',
            'substitute'     => 'nullable|string|max:120',
            'rows'           => 'required|array|min:1',
            'rows.*.serial'  => 'required|string',
            'rows.*.status'  => 'required|in:Present,Late,Excused,Absent',
            'rows.*.time_in' => 'nullable|date_format:H:i',
            'rows.*.remark'  => 'nullable|string|max:255',
        ]);

        $session = TrainingSession::findOrFail($v['session_id']);
        abort_unless($session->academic_year_id === $s->yearId(), 403, 'Attendance for an archived academic year cannot be changed.');

        $bySerial = $s->enrollments()->get()->keyBy(fn ($e) => $e->cadet->serial_no);
        foreach ($v['rows'] as $row) {
            if (! $bySerial->has($row['serial'])) {
                throw ValidationException::withMessages(['rows' => "Cadet {$row['serial']} is not in your platoon."]);
            }
        }

        DB::transaction(function () use ($v, $s, $bySerial, $session) {
            foreach ($v['rows'] as $row) {
                AttendanceRecord::updateOrCreate(
                    ['session_id' => $session->session_id, 'enrollment_id' => $bySerial[$row['serial']]->enrollment_id],
                    [
                        'logged_by_user_id'  => $s->user->user_id,
                        'attendance_status'  => $row['status'],
                        'time_in'            => ($row['time_in'] ?? null) ?: null,
                        'remarks'            => ($row['remark'] ?? null) ?: null,
                        'substitute_officer' => trim($v['substitute'] ?? '') ?: null,
                    ]
                );
            }
        });

        $by = trim($v['substitute'] ?? '') ? $v['substitute'].' (substitute for '.session('display_name').')' : session('display_name');

        return response()->json(['message' => 'Attendance record saved for '.$session->title.'. Logged by '.$by.'.']);
    }

    /* ---------- merit / demerit ---------- */

    public function merits(Request $r)
    {
        $s = Scope::for($r->user());
        $enr = $s->enrollments()->get();

        $entries = MeritDemeritEntry::with(['enrollment.cadet', 'loggedBy.cadet'])
            ->whereIn('enrollment_id', $enr->pluck('enrollment_id'))
            ->orderByDesc('entry_date')->orderByDesc('entry_id')->get()
            ->map(fn ($m) => Rows::platoonEntry($m))->all();

        return view('platoon.merits', [
            'role'     => $s->role,
            'offenses' => config('cadetcore.offenses'),
            'cadets'   => $enr->map(fn ($e) => [$e->enrollment_id, Ctx::name($e->cadet)])->sortBy(fn ($x) => $x[1])->values()->all(),
            'entries'  => $entries,
        ]);
    }

    public function storeMerit(Request $r)
    {
        $s = Scope::for($r->user());
        $offenses = config('cadetcore.offenses');

        $v = $r->validate([
            'enrollment_id' => 'required|integer',
            'type'          => 'required|in:Merit,Demerit',
            'points'        => 'exclude_unless:type,Merit|required|integer|min:1|max:20',
            'reason'        => 'exclude_unless:type,Merit|required|string|min:5|max:1000',
            'offenses'      => 'exclude_unless:type,Demerit|required|array|min:1',
            'offenses.*'    => 'string',
            'remarks'       => 'nullable|string|max:500',
            'substitute'    => 'nullable|string|max:120',
        ], ['offenses.required' => 'Tick at least one offense.', 'offenses.min' => 'Tick at least one offense.', 'reason.required' => 'A reason is required for every merit.']);

        $enrollment = $s->enrollments()->where('enrollment_id', $v['enrollment_id'])->first();
        if (! $enrollment) {
            throw ValidationException::withMessages(['enrollment_id' => 'That cadet is not in your platoon.']);
        }

        if ($v['type'] === 'Demerit') {
            $picked = array_values(array_unique($v['offenses']));
            foreach ($picked as $o) {
                if (! isset($offenses[$o])) {
                    throw ValidationException::withMessages(['offenses' => "Unknown offense: $o"]);
                }
            }
            // The server decides the points, never the browser.
            $points = array_sum(array_map(fn ($o) => $offenses[$o], $picked));
            $reason = implode('; ', $picked).(trim($v['remarks'] ?? '') ? ' ('.trim($v['remarks']).')' : '');
        } else {
            $points = (int) $v['points'];
            $reason = trim($v['reason']);
        }

        $entry = MeritDemeritEntry::create([
            'enrollment_id'      => $enrollment->enrollment_id,
            'logged_by_user_id'  => $s->user->user_id,
            'entry_date'         => today()->toDateString(),
            'entry_type'         => $v['type'],
            'points'             => $points,
            'reason'             => $reason,
            'substitute_officer' => trim($v['substitute'] ?? '') ?: null,
        ]);
        $entry->load(['enrollment.cadet', 'loggedBy.cadet']);

        return response()->json(['message' => 'Entry saved. The reason is attached to the cadet\'s record.', 'row' => Rows::platoonEntry($entry)], 201);
    }
}
