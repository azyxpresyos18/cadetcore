@extends('layouts.app')
@php
  // Merit / demerit data. The backend passes these; nothing is hardcoded here.
  $entries : rows of [entry id, date, cadet, battalion, 'Merit'|'Demerit', signed points, reason, logged by, audit text]
  $yearLabel = $yearLabel ?? null;
  $entries = $entries ?? [];
@endphp
@section('role', 'adjutant')
@section('title', 'Merit / Demerit')
@section('heading', 'Merit / Demerit Records')
@section('subtitle', 'Every entry includes a required reason'.($yearLabel ? ' · AY '.$yearLabel : ''))
@section('content')
<div class="filters">
  <button class="pill on" data-f="all">ALL</button><button class="pill" data-f="merit">MERIT</button><button class="pill" data-f="demerit">DEMERIT</button>
</div>
<div class="tablewrap"><table id="tbl">
  <thead><tr><th>Entry ID</th><th>Date</th><th>Cadet</th><th>Battalion</th><th>Type</th><th>Points</th><th>Reason / Justification</th><th>Logged by</th></tr></thead>
  <tbody>
  @forelse ($entries as $i => $e)
    <tr class="clickable" data-type="{{ strtolower($e[4]) }}" data-i="{{ $i }}">
      <td>{{ $e[0] }}</td><td>{{ $e[1] }}</td><td>{{ $e[2] }}</td><td>{{ $e[3] }}</td>
      <td><span class="badge b-{{ strtolower($e[4]) }}">{{ $e[4] }}</span></td>
      <td class="{{ str_starts_with($e[5], '+') ? 'pos' : 'neg' }}">{{ $e[5] }}</td><td>{{ $e[6] }}</td><td>{{ $e[7] }}</td>
    </tr>
  @empty
    <tr><td colspan="8">No merit or demerit entries yet.</td></tr>
  @endforelse
  </tbody></table></div>
<p style="color:var(--muted);margin-top:8px">Click a row to view the entry history.</p>

<dialog id="hist">
  <h3 id="h_title"></h3>
  <div class="kv"><span class="label">Cadet</span><strong id="h_cadet"></strong></div>
  <div class="kv"><span class="label">Merit / Demerit</span><strong id="h_pts"></strong></div>
  <div class="kv"><span class="label">Description</span><span id="h_reason"></span></div>
  <div class="kv"><span class="label">Audit trail</span><span id="h_audit"></span></div>
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
