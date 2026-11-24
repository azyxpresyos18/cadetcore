@extends('layouts.app')
@php
  // Dashboard data from the backend. The backend passes these; nothing is hardcoded here.
  // $recent : rows of [entry id, date, cadet name, battalion, 'Merit'|'Demerit', signed points e.g. '+8', reason, logged by]
  // $bn     : rows of [battalion name, bar width 0-100, '92 cadets']
  // $next   : null or ['title'=>, 'date'=>, 'time'=>'0600H - 1100H', 'venue'=>]
  $yearLabel = $yearLabel ?? null;
  $week = $week ?? null;
  $totalCadets = $totalCadets ?? 0;
  $officerCount = $officerCount ?? 0;
  $battalionCount = $battalionCount ?? 0;
  $recent = $recent ?? [];
  $bn = $bn ?? [];
  $next = $next ?? null;
@endphp
@section('role', 'adjutant')
@section('title', 'Dashboard')
@section('heading', 'Dashboard Overview')
@section('subtitle', $yearLabel ? 'AY '.$yearLabel.($week ? ' · Week '.$week : '') : 'No current academic year set')
@section('content')
<div class="stats">
  <div class="stat"><div class="label">Total cadets</div><div class="num">{{ $totalCadets }}</div><small>{{ $battalionCount }} {{ \Illuminate\Support\Str::plural('battalion', $battalionCount) }} active</small></div>
  <div class="stat"><div class="label">Officer accounts</div><div class="num">{{ $officerCount }}</div><small>Officers on file</small></div>
</div>
<div class="grid2">
  <div class="card">
    <h3>Recent Merit / Demerit Entries</h3>
    <div class="tablewrap"><table>
      <thead><tr><th>ID</th><th>Date</th><th>Cadet</th><th>Bn</th><th>Type</th><th>Pts</th><th>Reason</th><th>Logged by</th></tr></thead>
      <tbody>
      @forelse ($recent as $r)
        <tr><td>{{ $r[0] }}</td><td>{{ $r[1] }}</td><td>{{ $r[2] }}</td><td>{{ $r[3] }}</td>
          <td><span class="badge b-{{ strtolower($r[4]) }}">{{ $r[4] }}</span></td>
          <td class="{{ str_starts_with($r[5], '+') ? 'pos' : 'neg' }}">{{ $r[5] }}</td><td>{{ $r[6] }}</td><td>{{ $r[7] }}</td></tr>
      @empty
        <tr><td colspan="8">No merit or demerit entries yet.</td></tr>
      @endforelse
      </tbody></table></div>
  </div>
  <div>
    <div class="card"><h3>Battalion Standing</h3>
      @forelse ($bn as $b)<div><span>{{ $b[0] }}</span> <small style="float:right;color:var(--muted)">{{ $b[2] }}</small><div class="bar"><i style="width:{{ $b[1] }}%"></i></div></div>
      @empty<p style="color:var(--muted)">No cadets enrolled yet.</p>@endforelse
    </div>
    <div class="card"><h3>Next Session</h3>
      @if ($next)
        <strong>{{ $next['title'] }}</strong><p style="color:var(--muted);margin:4px 0">{{ $next['date'] }} · {{ $next['time'] }}<br>{{ $next['venue'] }}</p>
      @else
        <p style="color:var(--muted);margin:4px 0">No upcoming sessions scheduled.</p>
      @endif
      <a class="btn" href="{{ route($role === 'adjutant' ? 'adjutant.schedule' : ($role === 'corps_s1' ? 'corps.schedule' : 'battalion.schedule')) }}">View schedule</a></div>
  </div>
</div>
@endsection
