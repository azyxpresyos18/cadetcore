@extends('layouts.app')
@section('role', 'cadet')
@section('title', 'Merit & Demerit')
@section('heading', 'Merit & Demerit')
@section('subtitle', 'View only')
@section('content')
@php
  // SAMPLE DATA: replace with the logged-in cadet's records from the database.
  $rows = [
    ['M-0040','2026-09-10','Demerit','-3','Late arrival to formation','Lt. Dela Cruz'],
    ['M-0033','2026-08-28','Merit','+12','Excellent drill performance','Lt. Dela Cruz'],
    ['M-0021','2026-08-21','Merit','+6','Helped organize the field exercise','Sgt. Ramos'],
    ['M-0012','2026-08-09','Demerit','-3','Improper haircut during inspection','Lt. Dela Cruz'],
  ];
  $merit = 18; $demerit = 6;
@endphp
<div class="card"><div class="sum">
  <div><small>MERIT</small><span class="pos">+{{ $merit }}</span></div><span>&minus;</span>
  <div><small>DEMERIT</small><span class="neg">{{ $demerit }}</span></div><span>=</span>
  <div><small>NET STANDING</small><span class="pos">+{{ $merit - $demerit }}</span></div>
</div></div>
<div class="tablewrap"><table>
  <thead><tr><th>Entry ID</th><th>Date</th><th>Type</th><th>Points</th><th>Reason / Justification</th><th>Logged by</th></tr></thead>
  <tbody>
  @foreach ($rows as $r)
    <tr><td>{{ $r[0] }}</td><td>{{ $r[1] }}</td><td><span class="badge b-{{ strtolower($r[2]) }}">{{ $r[2] }}</span></td>
      <td class="{{ str_starts_with($r[3], '+') ? 'pos' : 'neg' }}">{{ $r[3] }}</td><td>{{ $r[4] }}</td><td>{{ $r[5] }}</td></tr>
  @endforeach
  </tbody></table></div>
<div class="alert note" style="margin-top:14px">Questions about an entry? Contact the Corps S1 Personnel Officer and provide the Entry ID.</div>
@endsection
