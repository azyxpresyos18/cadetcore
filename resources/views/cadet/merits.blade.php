@extends('layouts.app')
@php
  // My merit / demerit data. The backend passes these; nothing is hardcoded here.
  // $merit / $demerit : total points
  // $rows : rows of [entry id, date, 'Merit'|'Demerit', signed points, reason, logged by]
  $merit = $merit ?? 0;
  $demerit = $demerit ?? 0;
  $rows = $rows ?? [];
@endphp
@section('role', 'cadet')
@section('title', 'Merit & Demerit')
@section('heading', 'Merit & Demerit')
@section('subtitle', 'View only')
@section('content')
<div class="card"><div class="sum">
  <div><small>MERIT</small><span class="pos">+{{ $merit }}</span></div><span>&minus;</span>
  <div><small>DEMERIT</small><span class="neg">{{ $demerit }}</span></div><span>=</span>
  <div><small>NET STANDING</small><span class="{{ $merit - $demerit >= 0 ? 'pos' : 'neg' }}">{{ \App\Support\Ctx::net($merit - $demerit) }}</span></div>
</div></div>
<div class="tablewrap"><table>
  <thead><tr><th>Entry ID</th><th>Date</th><th>Type</th><th>Points</th><th>Reason / Justification</th><th>Logged by</th></tr></thead>
  <tbody>
  @forelse ($rows as $r)
    <tr><td>{{ $r[0] }}</td><td>{{ $r[1] }}</td><td><span class="badge b-{{ strtolower($r[2]) }}">{{ $r[2] }}</span></td>
      <td class="{{ str_starts_with($r[3], '+') ? 'pos' : 'neg' }}">{{ $r[3] }}</td><td>{{ $r[4] }}</td><td>{{ $r[5] }}</td></tr>
  @empty
    <tr><td colspan="6">No merit or demerit entries on your record.</td></tr>
  @endforelse
  </tbody></table></div>
<div class="alert note" style="margin-top:14px">Questions about an entry? Contact the Corps S1 Personnel Officer and provide the Entry ID.</div>
@endsection
