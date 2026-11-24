@extends('layouts.app')
@php
  // Platoon merit / demerit data. The backend passes these; nothing is hardcoded here.
  $cadets  : list of cadet names for the dropdown
  $entries : rows of [entry id, date, cadet, 'Merit'|'Demerit', points (unsigned int), reason, logged by]
  $cadets = $cadets ?? [];
  $entries = $entries ?? [];
@endphp
@section('role', 'platoon_leader')
@section('title', 'Merit / Demerit')
@section('heading', 'Merit / Demerit')
@section('subtitle', 'A reason is required for every entry')
@section('actions')<button class="btn" onclick="logDlg.showModal()">+ Log Entry</button>@endsection
@section('content')
@php
  // Official offense list. Each offense carries a fixed demerit value.
  $offenses = [
    ['Late',3],['No ball cap',3],['Not prescribed haircut (Male)',3],['Not prescribed hairdo (Female)',3],
    ['Dirty face',2],['Unshaved mustache (Male)',3],['Unnecessary accessories',3],['Not prescribed T-shirt',3],
    ['Long fingernails',3],['Dirty fingernails',2],['No belt and buckle',3],['Not prescribed belt and buckle',3],
    ['Not prescribed pants',3],['White socks',3],['Not prescribed shoes',3],['Dangling shoelace',3],
    ['Improper placement of shoelaces',2],['Improper folding of handkerchief',2],['Cramped handkerchief',2],
  ];
@endphp
<div id="msg"></div>
<div class="filters">
  <button class="pill on" data-f="all">ALL</button><button class="pill" data-f="merit">MERIT</button><button class="pill" data-f="demerit">DEMERIT</button>
</div>
<div class="tablewrap"><table id="tbl">
  <thead><tr><th>Entry ID</th><th>Date</th><th>Cadet</th><th>Type</th><th>Points</th><th>Reason</th><th>Logged by</th></tr></thead>
  <tbody>
  @forelse ($entries as $e)
    <tr data-type="{{ strtolower($e[3]) }}"><td>{{ $e[0] }}</td><td>{{ $e[1] }}</td><td>{{ $e[2] }}</td>
      <td><span class="badge b-{{ strtolower($e[3]) }}">{{ $e[3] }}</span></td>
      <td class="{{ $e[3] === 'Merit' ? 'pos' : 'neg' }}">{{ $e[3] === 'Merit' ? '+' : '-' }}{{ $e[4] }}</td><td>{{ $e[5] }}</td><td>{{ $e[6] }}</td></tr>
  @empty
    <tr><td colspan="7">No merit or demerit entries for your platoon yet.</td></tr>
  @endforelse
  </tbody></table></div>

<dialog id="logDlg" style="width:min(560px,94vw)">
  <form id="logForm" method="dialog">
    <h3>Log merit / demerit</h3>
    <label class="f"><span>Cadet</span><select id="l_cadet" required><option value="">Select cadet...</option>@foreach ($cadets as $c)<option>{{ $c }}</option>@endforeach</select></label>
    <label class="f"><span>Type</span><select id="l_type"><option>Merit</option><option>Demerit</option></select></label>

    <div id="meritBox">
      <label class="f"><span>Points</span><input type="number" id="l_pts" min="1" max="20"></label>
      <label class="f"><span>Reason (required)</span><textarea id="l_reason" rows="3" minlength="5" placeholder="Describe what happened"></textarea></label>
    </div>

    <div id="demeritBox" class="hide">
      <div class="label" style="font-size:10px;text-transform:uppercase;color:#6a6f50;margin-bottom:6px">Select the offense(s) committed</div>
      <div class="offenses">
        @foreach ($offenses as $i => [$name, $pts])
          <label class="off"><input type="checkbox" class="off-cb" value="{{ $name }}" data-pts="{{ $pts }}"><span>{{ $name }}</span><b>-{{ $pts }}</b></label>
        @endforeach
      </div>
      <div class="off-total">Total: <b id="offTotal">0</b> demerit points <small id="offCount">(0 offenses)</small></div>
      <label class="f"><span>Remarks (optional)</span><input id="l_remarks" placeholder="Extra details, if any"></label>
    </div>

    <label style="display:flex;gap:6px;align-items:center;font-weight:600;margin-bottom:8px"><input type="checkbox" id="l_sub" style="width:auto"> Logging as substitute officer</label>
    <label class="f hide" id="l_subbox"><span>Substitute officer name</span><input id="l_subname"></label>
    <div class="dlg-actions"><button type="button" class="btn ghost" style="color:var(--ink);border-color:var(--ink)" onclick="logDlg.close()">Cancel</button><button class="btn" value="save">Save entry</button></div>
  </form>
</dialog>
@endsection
@push('scripts')
<script>
const $ = id => document.getElementById(id);
const ME = @json(session('display_name'));
document.querySelectorAll('.pill').forEach(p => p.onclick = () => {
  document.querySelectorAll('.pill').forEach(x => x.classList.remove('on')); p.classList.add('on');
  document.querySelectorAll('#tbl tbody tr').forEach(r => r.classList.toggle('hide', p.dataset.f !== 'all' && r.dataset.type !== p.dataset.f));
});
$('l_sub').onchange = () => $('l_subbox').classList.toggle('hide', !$('l_sub').checked);
const cbs = [...document.querySelectorAll('.off-cb')];
function syncType() {
  const dem = $('l_type').value === 'Demerit';
  $('meritBox').classList.toggle('hide', dem); $('demeritBox').classList.toggle('hide', !dem);
  $('l_pts').required = !dem; $('l_reason').required = !dem;
}
function syncTotal() {
  const on = cbs.filter(c => c.checked);
  $('offTotal').textContent = on.reduce((t, c) => t + +c.dataset.pts, 0);
  $('offCount').textContent = `(${on.length} offense${on.length === 1 ? '' : 's'})`;
}
$('l_type').onchange = syncType; cbs.forEach(c => c.onchange = syncTotal); syncType();
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
$('logForm').addEventListener('submit', e => {
  if (e.submitter && e.submitter.value !== 'save') return;
  if ($('l_sub').checked && !$('l_subname').value.trim()) { e.preventDefault(); alert("Enter the substitute officer's name."); return; }
  const t = $('l_type').value; let pts, reason;
  if (t === 'Demerit') {
    const on = cbs.filter(c => c.checked);
    if (!on.length) { e.preventDefault(); alert('Tick at least one offense.'); return; }
    pts = on.reduce((s, c) => s + +c.dataset.pts, 0);
    reason = on.map(c => c.value).join('; ') + ($('l_remarks').value.trim() ? ' (' + $('l_remarks').value.trim() + ')' : '');
  } else { pts = $('l_pts').value; reason = $('l_reason').value; }
  const by = $('l_sub').checked ? $('l_subname').value + ' (substitute)' : ME;
  const tr = document.createElement('tr'); tr.dataset.type = t.toLowerCase();
  tr.innerHTML = `<td>(new)</td><td>${new Date().toISOString().slice(0, 10)}</td><td>${esc($('l_cadet').value)}</td><td><span class="badge b-${t.toLowerCase()}">${t}</span></td><td class="${t === 'Merit' ? 'pos' : 'neg'}">${t === 'Merit' ? '+' : '-'}${pts}</td><td>${esc(reason)}</td><td>${esc(by)}</td>`;
  document.querySelector('#tbl tbody').prepend(tr);
  $('msg').innerHTML = '<div class="alert ok">Entry saved. The reason is attached to the cadet\'s record.</div>';
  $('logForm').reset(); $('l_subbox').classList.add('hide'); syncType(); syncTotal();
});
</script>
@endpush
