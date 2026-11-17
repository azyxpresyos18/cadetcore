@extends('layouts.app')
@section('role', 'platoon_leader')
@section('title', 'Dashboard')
@section('heading', 'Platoon Dashboard')
@section('subtitle', '1st Battalion · Platoon 1 · AY 2026-2027 S1')
@section('content')
@php
  // SAMPLE DATA: replace with database queries later.
  $mine = [
    ['M-0041','2026-09-10','Santos, Maria L.','Merit','+8','Outstanding performance in drill'],
    ['M-0040','2026-09-10','Reyes, Marco A.','Demerit','-3','Late arrival to formation'],
    ['M-0029','2026-09-03','Perez, Ken D.','Merit','+4','Helped organize equipment'],
  ];
@endphp
<div class="stats">
  <div class="stat"><div class="label">Cadets in platoon</div><div class="num">32</div><small>Platoon 1</small></div>
  <div class="stat"><div class="label">Sessions logged</div><div class="num">5</div><small>this semester</small></div>
  <div class="stat"><div class="label">Entries by me</div><div class="num">{{ count($mine) }}</div><small>recent merits / demerits</small></div>
</div>
<div class="grid2">
  <div class="card"><h3>My Recent Entries</h3>
    <div class="tablewrap"><table>
      <thead><tr><th>ID</th><th>Date</th><th>Cadet</th><th>Type</th><th>Pts</th><th>Reason</th></tr></thead>
      <tbody>@foreach ($mine as $r)
        <tr><td>{{ $r[0] }}</td><td>{{ $r[1] }}</td><td>{{ $r[2] }}</td><td><span class="badge b-{{ strtolower($r[3]) }}">{{ $r[3] }}</span></td>
          <td class="{{ str_starts_with($r[4], '+') ? 'pos' : 'neg' }}">{{ $r[4] }}</td><td>{{ $r[5] }}</td></tr>
      @endforeach</tbody></table></div>
  </div>
  <div>
    <div class="card"><h3>Next Session</h3><strong>Drill Day 5</strong>
      <p style="color:var(--muted);margin:4px 0">Sunday, 13 Sep 2026 · 06:00 - 11:00<br>Parade Ground · All Cadets</p>
      <a class="btn" href="{{ route('platoon.attendance') }}">Take attendance</a></div>
    <div class="card"><h3>Quick actions</h3>
      <a class="btn" href="{{ route('platoon.merits') }}">Log merit / demerit</a>
      <a class="btn ghost" href="{{ route('platoon.schedule') }}">View schedule</a></div>
  </div>
</div>
@endsection
