@extends('layouts.app')
@php
  // Cadet records data. The backend passes these; nothing is hardcoded here.
  // $cadets : rows of [serial no, name, battalion, company, platoon, rank, 'BSIT-3', signed net points, status label, badge css class (b-good|b-warning|b-probation|b-neutral)]
  // $battalions, $companies, $platoons, $statuses : lists of strings for the filter / dialog dropdowns
  $yearLabel = $yearLabel ?? null;
  $totalCadets = $totalCadets ?? 0;
  $officerCount = $officerCount ?? 0;
  $activeCount = $activeCount ?? 0;
  $cadets = $cadets ?? [];
  $battalions = $battalions ?? [];
  $companies = $companies ?? [];
  $platoons = $platoons ?? [];
  $statuses = $statuses ?? [];
@endphp
@section('role', 'adjutant')
@section('title', 'Cadet Records')
@section('heading', 'Cadet Records')
@section('subtitle', $totalCadets.' '.\Illuminate\Support\Str::plural('cadet', $totalCadets).' enrolled'.($yearLabel ? ' · AY '.$yearLabel : ''))
@section('actions')
  @if (in_array($role ?? 'adjutant', ['adjutant','corps_s1']))
    <button class="btn" onclick="openAdd()">+ Add New Cadet</button>
  @endif
@endsection
@section('content')
@php
  // Row order: serial no, name, battalion, company, platoon, rank, course/year, net points, status, badge class
  $canEdit = in_array($role ?? 'adjutant', ['adjutant','corps_s1']);
@endphp
<div class="stats">
  <div class="stat"><div class="label">Total cadets</div><div class="num">{{ $totalCadets }}</div><small>All battalions</small></div>
  <div class="stat"><div class="label">Officer accounts</div><div class="num">{{ $officerCount }}</div><small>On file</small></div>
  <div class="stat"><div class="label">Active</div><div class="num">{{ $activeCount }}</div><small>of {{ $totalCadets }} enrollments</small></div>
  <div class="stat"><div class="label">Other status</div><div class="num sm">{{ $totalCadets - $activeCount }}</div><small>Needs review</small></div>
</div>
<div id="msg"></div>
<div class="filters">
  <input id="q" placeholder="Search name or serial no...">
  <select id="bn"><option value="">All battalions</option>@foreach ($battalions as $o)<option>{{ $o }}</option>@endforeach</select>
  <select id="co"><option value="">All companies</option>@foreach ($companies as $o)<option>{{ $o }}</option>@endforeach</select>
  <select id="pl"><option value="">All platoons</option>@foreach ($platoons as $o)<option>{{ $o }}</option>@endforeach</select>
  <select id="st"><option value="">All status</option>@foreach ($statuses as $o)<option>{{ $o }}</option>@endforeach</select>
</div>
<div class="tablewrap"><table id="tbl">
  <thead><tr><th>Serial No.</th><th>Name</th><th>Battalion</th><th>Company</th><th>Platoon</th><th>Rank</th><th>Course / Year</th><th>Net points</th><th>Status</th><th></th></tr></thead>
  <tbody>
  @forelse ($cadets as $c)
    <tr data-e="{{ json_encode($c[11] ?? []) }}" data-id="{{ $c[10] }}">
      <td>{{ $c[0] }}</td><td>{{ $c[1] }}</td><td>{{ $c[2] }}</td><td>{{ $c[3] }}</td><td>{{ $c[4] }}</td><td>{{ $c[5] }}</td><td>{{ $c[6] }}</td>
      <td class="{{ str_starts_with($c[7], '+') ? 'pos' : 'neg' }}">{{ $c[7] }}</td>
      <td><span class="badge {{ $c[9] }}">{{ $c[8] }}</span></td>
      <td>
        @if ($canEdit)
          <button class="btn ghost" style="padding:3px 10px" onclick="openEdit(this)">Edit</button>
          <button class="btn ghost" style="padding:3px 10px" onclick="removeRow(this, {{ $c[10] }})">Remove</button>
        @endif
      </td>
    </tr>
  @empty
    <tr><td colspan="10">No cadets are enrolled in the current academic year.</td></tr>
  @endforelse
  </tbody></table></div>

<dialog id="addDlg">
  <form id="addForm" method="dialog">
    <h3 id="addTitle">Add new cadet</h3>
    <div id="addErr"></div>
    <div class="alert note">Use the official roster format. The system checks required fields and duplicate serial numbers before saving.</div>
    <label class="f"><span>Cadet type</span><select id="a_type" required><option value="">Select...</option>@foreach ($cadetTypes ?? [] as $t)<option>{{ $t }}</option>@endforeach</select></label>
    <label class="f"><span>Last name</span><input id="a_ln" maxlength="100" placeholder="Dela Cruz" required></label>
    <label class="f"><span>First name</span><input id="a_fn" maxlength="100" placeholder="Juan" required></label>
    <label class="f"><span>Middle name <small>(optional)</small></span><input id="a_mn" maxlength="100" placeholder="Santos"></label>
    <label class="f"><span>Serial number</span><input id="a_sn" placeholder="CE-R25-002444" required pattern="CE-[A-Z]\d{2}-\d{6}" title="Format: CE-R25-002444"></label>
    <label class="f"><span>Battalion</span><select id="a_bn" required><option value="">Select...</option></select></label>
    <label class="f"><span>Company</span><select id="a_co" required><option value="">Select...</option></select></label>
    <label class="f"><span>Platoon</span><select id="a_pl" required><option value="">Select...</option></select></label>
    <label class="f"><span>Course and year level</span><input id="a_cy" maxlength="150" placeholder="e.g. Bachelor of Science in Information Systems-1" required></label>
    <div class="dlg-actions"><button type="button" class="btn ghost" style="color:var(--ink);border-color:var(--ink)" onclick="addDlg.close()">Cancel</button><button class="btn" value="save" id="addSubmit">Validate &amp; add cadet</button></div>
  </form>
</dialog>
@endsection
@push('scripts')
@include('partials.cascade')
<script>
const $q = document.getElementById('q');
function filt() {
  const q = $q.value.toLowerCase();
  document.querySelectorAll('#tbl tbody tr').forEach(r => {
    const t = r.innerText.toLowerCase(), c = r.children;
    r.classList.toggle('hide', !(t.includes(q)
      && (!bn.value || c[2].innerText === bn.value)
      && (!co.value || c[3].innerText === co.value)
      && (!pl.value || c[4].innerText === pl.value)
      && (!st.value || c[8].innerText === st.value)));
  });
}
[$q, bn, co, pl, st].forEach(el => el.oninput = filt);
const BASE = @json(url()->current());
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
cascade(a_bn, a_co, a_pl, @json($structure ?? []));
async function removeRow(b, id) {
  if (!confirm('Remove this cadet record? Their attendance and merit history is kept.')) return;
  try { await api('DELETE', `${BASE}/${id}`); b.closest('tr').remove(); }
  catch (err) { document.getElementById('msg').innerHTML = `<div class="alert err">${esc(err.message)}</div>`; }
}
let editId = null;
function openAdd() {
  editId = null; addForm.reset(); addErr.innerHTML = '';
  addTitle.textContent = 'Add new cadet'; addSubmit.textContent = 'Validate & add cadet';
  addDlg.showModal();
}
function openEdit(b) {
  const tr = b.closest('tr'), d = JSON.parse(tr.dataset.e || '{}');
  editId = tr.dataset.id; addErr.innerHTML = '';
  addTitle.textContent = 'Edit cadet'; addSubmit.textContent = 'Save changes';
  a_type.value = d.type || ''; a_ln.value = d.last || ''; a_fn.value = d.first || ''; a_mn.value = d.middle || '';
  a_sn.value = d.serial || ''; a_cy.value = d.course || '';
  a_bn.value = d.bn || ''; a_bn.dispatchEvent(new Event('change'));
  a_co.value = d.co || ''; a_co.dispatchEvent(new Event('change'));
  a_pl.value = d.pl || '';
  addDlg.showModal();
}
addForm.addEventListener('submit', async e => {
  if (e.submitter && e.submitter.value !== 'save') return;
  e.preventDefault();
  addErr.innerHTML = '';
  try {
    if (editId) {
      await api('PUT', `${BASE}/${editId}`, {cadet_type: a_type.value, last_name: a_ln.value.trim(), first_name: a_fn.value.trim(), middle_name: a_mn.value.trim(), serial: a_sn.value.trim(), battalion: a_bn.value, company: a_co.value, platoon: a_pl.value, course: a_cy.value.trim()});
      location.reload(); return;
    }
    const res = await api('POST', BASE, {cadet_type: a_type.value, last_name: a_ln.value.trim(), first_name: a_fn.value.trim(), middle_name: a_mn.value.trim(), serial: a_sn.value.trim(), battalion: a_bn.value, company: a_co.value, platoon: a_pl.value, course: a_cy.value.trim()});
    const c = res.row;
    const tr = document.createElement('tr');
    tr.dataset.e = JSON.stringify(c[11] || {}); tr.dataset.id = c[10];
    tr.innerHTML = `<td>${esc(c[0])}</td><td>${esc(c[1])}</td><td>${esc(c[2])}</td><td>${esc(c[3])}</td><td>${esc(c[4])}</td><td>${esc(c[5])}</td><td>${esc(c[6])}</td><td class="pos">${c[7]}</td><td><span class="badge ${c[9]}">${c[8]}</span></td><td><button class="btn ghost" style="padding:3px 10px" onclick="openEdit(this)">Edit</button> <button class="btn ghost" style="padding:3px 10px" onclick="removeRow(this, ${c[10]})">Remove</button></td>`;
    document.querySelector('#tbl tbody').prepend(tr);
    document.getElementById('msg').innerHTML = `<div class="alert ok">${esc(res.message)}</div>`;
    addForm.reset(); addDlg.close();
  } catch (err) { addErr.innerHTML = `<div class="alert err">${esc(err.message)}</div>`; }
});
</script>
@endpush
