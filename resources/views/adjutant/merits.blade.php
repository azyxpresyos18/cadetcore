@extends('layouts.app')
@section('role', 'adjutant')
@section('title', 'Merit / Demerit')
@section('heading', 'Merit / Demerit Records')
@section('subtitle', 'Every entry includes a required reason · AY 2026-2027 S1')
@section('content')
@php
  // SAMPLE DATA: replace with database queries later.
  $entries = [
    ['M-0041','2026-09-10','Santos, Maria L.','1st Bn','Merit','+8','Outstanding performance in drill','Lt. Dela Cruz','Encoded by Lt. Dela Cruz · 2026-09-10 at 14:02'],
    ['M-0040','2026-09-10','Reyes, Marco A.','1st Bn','Demerit','-3','Late arrival to formation','Lt. Dela Cruz','Encoded by Lt. Dela Cruz · 2026-09-10 at 14:04'],
    ['M-0037','2026-09-08','Cruz, Juan P.','2nd Bn','Merit','+5','Leadership excellence in field training','Sgt. Ramos','Encoded by Sgt. Ramos · 2026-09-08 at 16:30'],
    ['M-0035','2026-09-06','Lim, Jose R.','3rd Bn','Demerit','-5','Improper uniform during inspection','Lt. Garcia','Encoded by Lt. Garcia · 2026-09-06 at 11:15'],
    ['M-0033','2026-09-04','Tan, Ana C.','2nd Bn','Merit','+4','Volunteered for event setup','Sgt. Ramos','Encoded by Sgt. Ramos · 2026-09-04 at 10:00'],
  ];
@endphp
<div class="filters">
  <button class="pill on" data-f="all">ALL</button><button class="pill" data-f="merit">MERIT</button><button class="pill" data-f="demerit">DEMERIT</button>
</div>
<div class="tablewrap"><table id="tbl">
  <thead><tr><th>Entry ID</th><th>Date</th><th>Cadet</th><th>Battalion</th><th>Type</th><th>Points</th><th>Reason / Justification</th><th>Logged by</th></tr></thead>
  <tbody>
  @foreach ($entries as $i => $e)
    <tr class="clickable" data-type="{{ strtolower($e[4]) }}" data-i="{{ $i }}">
      <td>{{ $e[0] }}</td><td>{{ $e[1] }}</td><td>{{ $e[2] }}</td><td>{{ $e[3] }}</td>
      <td><span class="badge b-{{ strtolower($e[4]) }}">{{ $e[4] }}</span></td>
      <td class="{{ str_starts_with($e[5], '+') ? 'pos' : 'neg' }}">{{ $e[5] }}</td><td>{{ $e[6] }}</td><td>{{ $e[7] }}</td>
    </tr>
  @endforeach
  </tbody></table></div>
<p style="color:var(--muted);margin-top:8px">Click a row to view the entry history.</p>

<dialog id="hist">
  <h3 id="h_title"></h3>
  <div class="kv"><span class="label">Cadet</span><strong id="h_cadet"></strong></div>
  <div class="kv"><span class="label">Merit / Demerit</span><strong id="h_pts"></strong></div>
  <div class="kv"><span class="label">Description</span><span id="h_reason"></span></div>
  <div class="kv"><span class="label">Audit trail</span><span id="h_audit"></span><br>Reviewed by Corps S1: no changes recorded</div>
  <div class="dlg-actions"><button class="btn" onclick="hist.close()">Close history</button></div>
</dialog>
@endsection
@push('scripts')
<script>
const E = @json($entries);
document.querySelectorAll('.pill').forEach(p => p.onclick = () => {
  document.querySelectorAll('.pill').forEach(x => x.classList.remove('on')); p.classList.add('on');
  document.querySelectorAll('#tbl tbody tr').forEach(r => r.classList.toggle('hide', p.dataset.f !== 'all' && r.dataset.type !== p.dataset.f));
});
document.querySelectorAll('#tbl tbody tr').forEach(r => r.onclick = () => {
  const e = E[r.dataset.i];
  h_title.textContent = 'Entry history · ' + e[0]; h_cadet.textContent = e[2]; h_pts.textContent = e[4] + ': ' + e[5];
  h_reason.textContent = e[6]; h_audit.textContent = e[8]; hist.showModal();
});
</script>
@endpush
