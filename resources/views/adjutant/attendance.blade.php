@extends('layouts.app')
@php
  // Attendance data. The backend passes these; nothing is hardcoded here.
  // $rows            : rows of [serial no, cadet, battalion, platoon, 'Present'|'Late'|'Excused'|'Absent', time in or '-', remarks]
  // $sessions        : rows of [session id, 'Title · 13 Sep 2026']   (the page reloads with ?session=<id>)
  // $selectedSession : id of the session being shown, or null
  // $tally           : counts per status for the selected session
  $rows = $rows ?? [];
  $sessions = $sessions ?? [];
  $selectedSession = $selectedSession ?? null;
  $tally = $tally ?? ['Present'=>0,'Late'=>0,'Excused'=>0,'Absent'=>0];
  $battalions = $battalions ?? [];
@endphp
@section('role', 'adjutant')
@section('title', 'Attendance')
@section('heading', 'Attendance')
@section('subtitle', 'Attendance logged by Platoon Leaders · view only')
@section('content')
<div class="stats">
  <div class="stat"><div class="label">Present</div><div class="num">{{ $tally['Present'] }}</div><small>{{ $selectedSession ? 'this session' : 'no session' }}</small></div>
  <div class="stat"><div class="label">Late</div><div class="num">{{ $tally['Late'] }}</div></div>
  <div class="stat"><div class="label">Excused</div><div class="num">{{ $tally['Excused'] }}</div></div>
  <div class="stat"><div class="label">Absent</div><div class="num sm">{{ $tally['Absent'] }}</div></div>
</div>
<div class="filters">
  <select id="sess" onchange="location.search = '?session=' + this.value">@forelse ($sessions as $s)<option value="{{ $s[0] }}" @selected($s[0] == $selectedSession)>{{ $s[1] }}</option>@empty<option>No sessions yet</option>@endforelse</select>
  <select id="bn"><option value="">All battalions</option>@foreach ($battalions as $o)<option>{{ $o }}</option>@endforeach</select>
  <select id="st"><option value="">All status</option><option>Present</option><option>Late</option><option>Excused</option><option>Absent</option></select>
</div>
<div class="tablewrap"><table id="tbl">
  <thead><tr><th>Student No.</th><th>Cadet</th><th>Battalion</th><th>Platoon</th><th>Status</th><th>Time in</th><th>Remarks</th></tr></thead>
  <tbody>
  @forelse ($rows as $r)
    <tr><td>{{ $r[0] }}</td><td>{{ $r[1] }}</td><td>{{ $r[2] }}</td><td>{{ $r[3] }}</td>
      <td><span class="badge b-{{ strtolower($r[4]) }}">{{ $r[4] }}</span></td><td>{{ $r[5] }}</td><td>{{ $r[6] }}</td></tr>
  @empty
    <tr><td colspan="7">No attendance has been logged for this session.</td></tr>
  @endforelse
  </tbody></table></div>
@endsection
@push('scripts')
<script>
[document.getElementById('bn'), document.getElementById('st')].forEach(el => el.onchange = () => {
  document.querySelectorAll('#tbl tbody tr').forEach(r => {
    const c = r.children;
    r.classList.toggle('hide', (bn.value && c[2].innerText !== bn.value) || (st.value && c[4].innerText !== st.value));
  });
});
</script>
@endpush
