@extends('layouts.app')
@section('role', 'adjutant')
@section('title', 'Dashboard')
@section('heading', 'Dashboard Overview')
@section('subtitle', 'AY 2026-2027 · Semester 1 · Week 4')
@section('content')
@php
  // SAMPLE DATA: replace with database queries later.
  $recent = [
    ['M-0041','2026-09-10','Santos, Maria L.','1st Bn','Merit','+8','Outstanding performance in drill','Lt. Dela Cruz'],
    ['M-0040','2026-09-10','Reyes, Marco A.','1st Bn','Demerit','-3','Late arrival to formation','Lt. Dela Cruz'],
    ['M-0037','2026-09-08','Cruz, Juan P.','2nd Bn','Merit','+5','Leadership excellence in field training','Sgt. Ramos'],
    ['M-0035','2026-09-06','Lim, Jose R.','3rd Bn','Demerit','-5','Improper uniform during inspection','Lt. Garcia'],
  ];
  $bn = [['1st Battalion',92,'92 cadets'],['2nd Battalion',81,'81 cadets'],['3rd Battalion',75,'75 cadets']];
@endphp
<div class="stats">
  <div class="stat"><div class="label">Total cadets</div><div class="num">248</div><small>3 battalions active</small></div>
  <div class="stat"><div class="label">Officer accounts</div><div class="num">43</div><small>Officers on file</small></div>
</div>
<div class="grid2">
  <div class="card">
    <h3>Recent Merit / Demerit Entries</h3>
    <div class="tablewrap"><table>
      <thead><tr><th>ID</th><th>Date</th><th>Cadet</th><th>Bn</th><th>Type</th><th>Pts</th><th>Reason</th><th>Logged by</th></tr></thead>
      <tbody>
      @foreach ($recent as $r)
        <tr><td>{{ $r[0] }}</td><td>{{ $r[1] }}</td><td>{{ $r[2] }}</td><td>{{ $r[3] }}</td>
          <td><span class="badge b-{{ strtolower($r[4]) }}">{{ $r[4] }}</span></td>
          <td class="{{ str_starts_with($r[5], '+') ? 'pos' : 'neg' }}">{{ $r[5] }}</td><td>{{ $r[6] }}</td><td>{{ $r[7] }}</td></tr>
      @endforeach
      </tbody></table></div>
  </div>
  <div>
    <div class="card"><h3>Battalion Standing</h3>
      @foreach ($bn as $b)<div><span>{{ $b[0] }}</span> <small style="float:right;color:var(--muted)">{{ $b[2] }}</small><div class="bar"><i style="width:{{ $b[1] }}%"></i></div></div>@endforeach
    </div>
    <div class="card"><h3>Next Session</h3>
      <strong>Drill Day 5</strong><p style="color:var(--muted);margin:4px 0">Sunday, 13 Sep 2026 · 06:00 - 11:00<br>Parade Ground · All Cadets</p>
      <a class="btn" href="{{ route('adjutant.schedule') }}">View schedule</a></div>
  </div>
</div>
@endsection
