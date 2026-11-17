@extends('layouts.app')
@section('role', 'cadet')
@section('title', 'My Attendance')
@section('heading', 'My Attendance')
@section('subtitle', 'Personal attendance history · view only')
@section('content')
@php
  // SAMPLE DATA: replace with the logged-in cadet's records from the database.
  $rows = [
    ['13 Sep 2026','4th Military Instruction','Present','05:52','On time'],
    ['06 Sep 2026','3rd Military Instruction','Late','06:15','15 minutes late'],
    ['30 Aug 2026','2nd Military Instruction','Present','05:49','On time'],
    ['23 Aug 2026','1st Military Instruction','Excused','-','Medical certificate verified'],
  ];
@endphp
<div class="stats">
  <div class="stat"><div class="label">Present</div><div class="num">15</div><small>90% of sessions</small></div>
  <div class="stat"><div class="label">Late</div><div class="num">1</div><small>6% of sessions</small></div>
  <div class="stat"><div class="label">Excused</div><div class="num">1</div><small>Verified absence</small></div>
  <div class="stat"><div class="label">Absent</div><div class="num sm">1</div><small>Unexcused</small></div>
</div>
<div class="tablewrap"><table>
  <thead><tr><th>Date</th><th>Session</th><th>Status</th><th>Time in</th><th>Remarks</th></tr></thead>
  <tbody>
  @foreach ($rows as $r)
    <tr><td>{{ $r[0] }}</td><td>{{ $r[1] }}</td><td><span class="badge b-{{ strtolower($r[2]) }}">{{ $r[2] }}</span></td><td>{{ $r[3] }}</td><td>{{ $r[4] }}</td></tr>
  @endforeach
  </tbody></table></div>
@endsection
