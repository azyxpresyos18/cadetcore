@extends('layouts.app')
@section('role', 'adjutant')
@section('title', 'Attendance')
@section('heading', 'Attendance')
@section('subtitle', 'Attendance logged by Platoon Leaders · view only')
@section('content')
@php
  // SAMPLE DATA: replace with database queries later.
  $rows = [
    ['CE-R24-000012','Reyes, Marco A.','1st Bn','Plt 1','Present','05:52','On time'],
    ['CE-R24-000031','Santos, Maria L.','1st Bn','Plt 1','Present','05:48','On time'],
    ['CE-R23-000087','Cruz, Juan P.','2nd Bn','Plt 3','Late','06:15','15 minutes late'],
    ['CE-R25-000104','Lim, Jose R.','3rd Bn','Plt 2','Absent','-','No reason given'],
    ['CE-R25-000120','Tan, Ana C.','2nd Bn','Plt 3','Excused','-','Medical certificate verified'],
  ];
@endphp
<div class="stats">
  <div class="stat"><div class="label">Present</div><div class="num">2</div><small>this session</small></div>
  <div class="stat"><div class="label">Late</div><div class="num">1</div></div>
  <div class="stat"><div class="label">Excused</div><div class="num">1</div></div>
  <div class="stat"><div class="label">Absent</div><div class="num sm">1</div></div>
</div>
<div class="filters">
  <select id="sess"><option>Drill Day 5 · 13 Sep 2026</option><option>Drill Day 4 · 06 Sep 2026</option></select>
  <select id="bn"><option value="">All battalions</option><option>1st Bn</option><option>2nd Bn</option><option>3rd Bn</option></select>
  <select id="st"><option value="">All status</option><option>Present</option><option>Late</option><option>Excused</option><option>Absent</option></select>
</div>
<div class="tablewrap"><table id="tbl">
  <thead><tr><th>Student No.</th><th>Cadet</th><th>Battalion</th><th>Platoon</th><th>Status</th><th>Time in</th><th>Remarks</th></tr></thead>
  <tbody>
  @foreach ($rows as $r)
    <tr><td>{{ $r[0] }}</td><td>{{ $r[1] }}</td><td>{{ $r[2] }}</td><td>{{ $r[3] }}</td>
      <td><span class="badge b-{{ strtolower($r[4]) }}">{{ $r[4] }}</span></td><td>{{ $r[5] }}</td><td>{{ $r[6] }}</td></tr>
  @endforeach
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
