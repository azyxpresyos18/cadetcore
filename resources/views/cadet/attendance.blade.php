@extends('layouts.app')
@php
  // My attendance data. The backend passes these; nothing is hardcoded here.
  // $tally : ['present'=>[count, percent], 'late'=>[..], 'excused'=>[..], 'absent'=>[..]]
  // $rows  : rows of ['13 Sep 2026', session title, 'Present'|'Late'|'Excused'|'Absent', time in or '-', remarks]
  $tally = $tally ?? ['present'=>[0,0],'late'=>[0,0],'excused'=>[0,0],'absent'=>[0,0]];
  $rows = $rows ?? [];
@endphp
@section('role', 'cadet')
@section('title', 'My Attendance')
@section('heading', 'My Attendance')
@section('subtitle', 'Personal attendance history · view only')
@section('content')
<div class="stats">
  <div class="stat"><div class="label">Present</div><div class="num">{{ $tally['present'][0] }}</div><small>{{ $tally['present'][1] }}% of sessions</small></div>
  <div class="stat"><div class="label">Late</div><div class="num">{{ $tally['late'][0] }}</div><small>{{ $tally['late'][1] }}% of sessions</small></div>
  <div class="stat"><div class="label">Excused</div><div class="num">{{ $tally['excused'][0] }}</div><small>Verified absence</small></div>
  <div class="stat"><div class="label">Absent</div><div class="num sm">{{ $tally['absent'][0] }}</div><small>Unexcused</small></div>
</div>
<div class="tablewrap"><table>
  <thead><tr><th>Date</th><th>Session</th><th>Status</th><th>Time in</th><th>Remarks</th></tr></thead>
  <tbody>
  @forelse ($rows as $r)
    <tr><td>{{ $r[0] }}</td><td>{{ $r[1] }}</td><td><span class="badge b-{{ strtolower($r[2]) }}">{{ $r[2] }}</span></td><td>{{ $r[3] }}</td><td>{{ $r[4] }}</td></tr>
  @empty
    <tr><td colspan="5">No attendance has been recorded for you yet.</td></tr>
  @endforelse
  </tbody></table></div>
@endsection
