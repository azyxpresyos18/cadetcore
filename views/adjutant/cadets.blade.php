@extends('layouts.app')
@php
  // Cadet records data. The backend passes these; nothing is hardcoded here.
  $cadets : rows of [serial no, name, battalion, company, platoon, rank, 'BSIT-3', signed net points, status label, badge css class (b-good|b-warning|b-probation|b-neutral)]
  $battalions, $companies, $platoons, $statuses : lists of strings for the filter / dialog dropdowns
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
    <button class="btn" onclick="addDlg.showModal()">+ Add New Cadet</button>
  @endif
@endsection
@section('content')
@php
  // Row order: student no, name, battalion, company, platoon, rank, course/year, net points, status, badge class
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
  <input id="q" placeholder="Search name or student no...">
  <select id="bn"><option value="">All battalions</option>@foreach ($battalions as $o)<option>{{ $o }}</option>@endforeach</select>
  <select id="co"><option value="">All companies</option>@foreach ($companies as $o)<option>{{ $o }}</option>@endforeach</select>
  <select id="pl"><option value="">All platoons</option>@foreach ($platoons as $o)<option>{{ $o }}</option>@endforeach</select>
  <select id="st"><option value="">All status</option>@foreach ($statuses as $o)<option>{{ $o }}</option>@endforeach</select>
</div>
<div class="tablewrap"><table id="tbl">
  <thead><tr><th>Student No.</th><th>Name</th><th>Battalion</th><th>Company</th><th>Platoon</th><th>Rank</th><th>Course / Year</th><th>Net points</th><th>Status</th><th></th></tr></thead>
  <tbody>
  @forelse ($cadets as $c)
    <tr>
      <td>{{ $c[0] }}</td><td>{{ $c[1] }}</td><td>{{ $c[2] }}</td><td>{{ $c[3] }}</td><td>{{ $c[4] }}</td><td>{{ $c[5] }}</td><td>{{ $c[6] }}</td>
      <td class="{{ str_starts_with($c[7], '+') ? 'pos' : 'neg' }}">{{ $c[7] }}</td>
      <td><span class="badge {{ $c[9] }}">{{ $c[8] }}</span></td>
      <td>
        @if ($canEdit)
          <button class="btn ghost" style="padding:3px 10px" onclick="removeRow(this)">Remove</button>
        @endif
      </td>
    </tr>
  @empty
    <tr><td colspan="10">No cadets are enrolled in the current academic year.</td></tr>
  @endforelse
  </tbody></table></div>

<dialog id="addDlg">
  <form id="addForm" method="dialog">
    <h3>Add new cadet</h3>
    <div class="alert note">Use the official roster format. The system checks required fields and duplicate serial numbers before saving.</div>
    <label class="f"><span>Full name</span><input id="a_name" placeholder="Surname, First Name M." required></label>
    <label class="f"><span>Serial number</span><input id="a_sn" placeholder="CE-R25-002444" required pattern="CE-[A-Z]\d{2}-\d{6}" title="Format: CE-R25-002444"></label>
    <label class="f"><span>Battalion</span><select id="a_bn" required><option value="">Select...</option>@foreach ($battalions as $o)<option>{{ $o }}</option>@endforeach</select></label>
    <label class="f"><span>Company</span><select id="a_co" required><option value="">Select...</option>@foreach ($companies as $o)<option>{{ $o }}</option>@endforeach</select></label>
    <label class="f"><span>Platoon</span><select id="a_pl" required><option value="">Select...</option>@foreach ($platoons as $o)<option>{{ $o }}</option>@endforeach</select></label>
    <label class="f"><span>Course and year</span><input id="a_cy" placeholder="e.g. BSIT-1" required></label>
    <div class="dlg-actions"><button type="button" class="btn ghost" style="color:var(--ink);border-color:var(--ink)" onclick="addDlg.close()">Cancel</button><button class="btn" value="save">Validate &amp; add cadet</button></div>
  </form>
</dialog>
@endsection
@push('scripts')
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
function removeRow(b) { if (confirm('Remove this cadet record?')) b.closest('tr').remove(); }
addForm.addEventListener('submit', e => {
  if (e.submitter && e.submitter.value !== 'save') return;
  const sn = a_sn.value.trim();
  if ([...document.querySelectorAll('#tbl tbody tr td:first-child')].some(td => td.innerText === sn)) {
    e.preventDefault(); alert('Duplicate serial number: this cadet already exists.'); return;
  }
  const tr = document.createElement('tr');
  tr.innerHTML = `<td>${sn}</td><td>${a_name.value}</td><td>${a_bn.value}</td><td>${a_co.value}</td><td>${a_pl.value}</td><td>Cadet</td><td>${a_cy.value}</td><td class="pos">+0</td><td><span class="badge b-good">Good Standing</span></td><td><button class="btn ghost" style="padding:3px 10px" onclick="removeRow(this)">Remove</button></td>`;
  document.querySelector('#tbl tbody').prepend(tr);
  document.getElementById('msg').innerHTML = `<div class="alert ok">Cadet ${a_name.value} was added successfully.</div>`;
  addForm.reset();
});
</script>
@endpush
