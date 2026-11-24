<?php

namespace App\Http\Controllers;

use App\Models\{CadetType, TrainingSession};
use App\Support\{Ctx, Scope};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    /** Calendar page for every role (cadets only get sessions meant for their cadet type). */
    public function show(Request $r)
    {
        $s = Scope::for($r->user());
        $typeCount = CadetType::count();

        $sessions = $s->sessions()->orderBy('session_date')->orderBy('start_time')->get()
            ->map(fn ($x) => Ctx::sessionRow($x, $typeCount))->values()->all();

        return view($s->role === 'cadet' ? 'cadet.schedule' : 'adjutant.schedule', [
            'role'            => $s->role,
            'sessions'        => $sessions,
            'audienceOptions' => Ctx::audienceOptions(),
        ]);
    }

    public function store(Request $r)
    {
        $s = Scope::for($r->user());
        abort_unless($s->year, 422, 'There is no current academic year.');

        [$data, $typeIds] = $this->validated($r);

        $session = DB::transaction(function () use ($s, $data, $typeIds) {
            $session = TrainingSession::create($data + [
                'academic_year_id'  => $s->yearId(),
                'posted_by_user_id' => $s->user->user_id,
            ]);
            $session->audiences()->sync($typeIds);
            return $session;
        });

        return response()->json(Ctx::sessionRow($session->load('audiences'), CadetType::count()), 201);
    }

    public function update(Request $r, TrainingSession $trainingSession)
    {
        $s = Scope::for($r->user());
        $this->ensureEditable($s, $trainingSession);

        [$data, $typeIds] = $this->validated($r);

        DB::transaction(function () use ($trainingSession, $data, $typeIds) {
            $trainingSession->update($data);
            $trainingSession->audiences()->sync($typeIds);
        });

        return response()->json(Ctx::sessionRow($trainingSession->load('audiences'), CadetType::count()));
    }

    public function destroy(Request $r, TrainingSession $trainingSession)
    {
        $s = Scope::for($r->user());
        $this->ensureEditable($s, $trainingSession);

        // Attendance already taken for this session is a permanent record, so the session stays.
        if (\App\Models\AttendanceRecord::where('session_id', $trainingSession->session_id)->exists()) {
            return response()->json(['message' => 'Attendance has already been logged for this session, so it cannot be removed.'], 409);
        }

        DB::transaction(function () use ($trainingSession) {
            $trainingSession->audiences()->detach();
            $trainingSession->delete();
        });

        return response()->json(['ok' => true]);
    }

    private function ensureEditable(Scope $s, TrainingSession $t): void
    {
        // Sessions of archived academic years are read-only.
        abort_unless($t->academic_year_id === $s->yearId(), 403, 'Sessions from an archived academic year cannot be changed.');
    }

    /** @return array{0: array, 1: array} [columns for training_sessions, cadet_type ids for the audience] */
    private function validated(Request $r): array
    {
        $v = $r->validate([
            'title'    => 'required|string|max:150',
            'date'     => 'required|date',
            'time'     => ['required', 'regex:/^\s*\d{1,2}:?\d{2}\s*[hH]?\s*-\s*\d{1,2}:?\d{2}\s*[hH]?\s*$/'],
            'location' => 'required|string|max:150',
            'type'     => 'required|string|max:40',
            'audience' => 'required|string|max:100',
        ], ['time.regex' => 'Time must look like 0600H - 1100H.']);

        [$start, $end] = array_map('trim', explode('-', $v['time']));
        $start = $this->hhmm($start);
        $end = $this->hhmm($end);
        if (! $start || ! $end || $end <= $start) {
            throw ValidationException::withMessages(['time' => 'The end time must be later than the start time.']);
        }

        $types = CadetType::orderBy('cadet_type_id')->get();
        $label = trim($v['audience']);
        $typeIds = strcasecmp($label, 'All Cadets') === 0
            ? $types->pluck('cadet_type_id')->all()
            : $types->filter(fn ($t) => strcasecmp($t->type_name, $label) === 0)->pluck('cadet_type_id')->all();

        if (! $typeIds) {
            throw ValidationException::withMessages(['audience' => 'Unknown audience. Pick one from the list.']);
        }

        return [[
            'title'        => trim($v['title']),
            'session_date' => $v['date'],
            'start_time'   => $start,
            'end_time'     => $end,
            'venue'        => trim($v['location']),
            'session_type' => trim($v['type']),
        ], $typeIds];
    }

    private function hhmm(string $t): ?string
    {
        $t = preg_replace('/[^0-9:]/', '', $t);
        if (! str_contains($t, ':')) {
            $t = str_pad($t, 4, '0', STR_PAD_LEFT);
            $t = substr($t, 0, -2).':'.substr($t, -2);
        }
        [$h, $m] = array_map('intval', explode(':', $t));
        return ($h > 23 || $m > 59) ? null : sprintf('%02d:%02d', $h, $m);
    }
}
