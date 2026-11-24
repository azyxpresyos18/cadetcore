@extends('layouts.app')
@php
  // Platoon dashboard data. The backend passes these; nothing is hardcoded here.
  $unit : e.g. 'Battalion name · Platoon name'
  $mine : rows of [entry id, date, cadet, 'Merit'|'Demerit', signed points, reason]
  $next : null or ['title'=>, 'date'=>, 'time'=>, 'venue'=>]
  $unit = $unit ?? 'Platoon';
  $yearLabel = $yearLabel ?? null;
  $platoonName = $platoonName ?? null;
  $cadetCount = $cadetCount ?? 0;
  $sessionsLogged = $sessionsLogged ?? 0;
  $minePerType = $minePerType ?? 0;
  $mine = $mine ?? [];
  $next = $next ?? null;
@endphp
@section('role', 'platoon_leader')
@section('title', 'Dashboard')
@section('heading', 'Platoon Dashboard')
@section('subtitle', $unit.($yearLabel ? ' · AY '.$yearLabel : ''))
@section('content')
<div class="stats">
  <div class="stat"><div class="label">Cadets in platoon</div><div class="num">{{ $cadetCount }}</div><small>{{ $platoonName ?? 'No platoon' }}</small></div>
  <div class="stat"><div class="label">Sessions logged</div><div class="num">{{ $sessionsLogged }}</div><small>this academic year</small></div>
  <div class="stat"><div class="label">Entries by me</div><div class="num">{{ $minePerType }}</div><small>merits / demerits logged</small></div>
</div>
<div class="grid2">
  <div class="card"><h3>My Recent Entries</h3>
    <div class="tablewrap"><table>
      <thead><tr><th>ID</th><th>Date</th><th>Cadet</th><th>Type</th><th>Pts</th><th>Reason</th></tr></thead>
      <tbody>@forelse ($mine as $r)
        <tr><td>{{ $r[0] }}</td><td>{{ $r[1] }}</td><td>{{ $r[2] }}</td><td><span class="badge b-{{ strtolower($r[3]) }}">{{ $r[3] }}</span></td>
          <td class="{{ str_starts_with($r[4], '+') ? 'pos' : 'neg' }}">{{ $r[4] }}</td><td>{{ $r[5] }}</td></tr>
      @empty<tr><td colspan="6">You have not logged any entries yet.</td></tr>@endforelse</tbody></table></div>
  </div>
  <div>
    <div class="card"><h3>Next Session</h3>@if ($next)<strong>{{ $next['title'] }}</strong>
      <p style="color:var(--muted);margin:4px 0">{{ $next['date'] }} · {{ $next['time'] }}<br>{{ $next['venue'] }}</p>
      @else<p style="color:var(--muted);margin:4px 0">No upcoming sessions scheduled.</p>@endif
      <a class="btn" href="{{ route('platoon.attendance') }}">Take attendance</a></div>
    <div class="card"><h3>Quick actions</h3>
      <a class="btn" href="{{ route('platoon.merits') }}">Log merit / demerit</a>
      <a class="btn ghost" href="{{ route('platoon.schedule') }}">View schedule</a></div>
  </div>
</div>
@endsection
