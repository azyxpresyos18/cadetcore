@extends('layouts.app')
@section('role', 'adjutant')
@section('title', 'Cadet Records')
@section('heading', 'Cadet Records')
@section('subtitle', '248 cadets enrolled · 3 battalions · AY 2026-2027')
@section('actions')
  @if (in_array($role ?? 'adjutant', ['adjutant','corps_s1']))
    <button class="btn" onclick="addDlg.showModal()">+ Add New Cadet</button>
  @endif
@endsection
@section('content')
@php
  // SAMPLE DATA: replace with database queries later.
  // Order: student no, name, battalion, company, platoon, role, course/year, net points, status
  $cadets = [
    ['CE-R24-000012','Reyes, Marco A.','1st Bn','Alpha Co','Plt 1','Cadet','BSIT-3','+12','Good Standing'],
    ['CE-R24-000031','Santos, Maria L.','1st Bn','Alpha Co','Plt 1','Platoon Leader','BSIT-3','+20','Good Standing'],
    ['CE-R23-000087','Cruz, Juan P.','2nd Bn','Bravo Co','Plt 3','Cadet','BSCS-4','+5','Warning'],
    ['CE-R25-000104','Lim, Jose R.','3rd Bn','Alpha Co','Plt 2','Cadet','BSIT-2','-5','Probation'],
    ['CE-R25-000120','Tan, Ana C.','2nd Bn','Bravo Co','Plt 3','Cadet','BSIS-2','+4','Good Standing'],
  ];
  $canEdit = in_array($role ?? 'adjutant', ['adjutant','corps_s1']);
@endphp
<div class="stats">
  <div class="stat"><div class="label">Total cadets</div><div class="num">248</div><small>All battalions</small></div>
  <div class="stat"><div class="label">Officer accounts</div><div class="num">43</div><small>Active</small></div>
  <div class="stat"><div class="label">Good standing</div><div class="num">231</div><small>of 248 cadets</small></div>
  <div class="stat"><div class="label">Probation</div><div class="num sm">4</div><small>Needs review</small></div>
</div>
<div id="msg"></div>
<div class="filters">
  <input id="q" placeholder="Search name or student no...">
  <select id="bn"><option value="">All battalions</option><option>1st Bn</option><option>2nd Bn</option><option>3rd Bn</option></select>
  <select id="co"><option value="">All companies</option><option>Alpha Co</option><option>Bravo Co</option></select>
  <select id="pl"><option value="">All platoons</option><option>Plt 1</option><option>Plt 2</option><option>Plt 3</option></select>
  <select id="st"><option value="">All status</option><option>Good Standing</option><option>Warning</option><option>Probation</option></select>
</div>
<div class="tablewrap"><table id="tbl">
  <thead><tr><th>Student No.</th><th>Name</th><th>Battalion</th><th>Company</th><th>Platoon</th><th>Role</th><th>Course / Year</th><th>Net points</th><th>Status</th><th></th></tr></thead>
  <tbody>
  @foreach ($cadets as $c)
    <tr>
      <td>{{ $c[0] }}</td><td>{{ $c[1] }}</td><td>{{ $c[2] }}</td><td>{{ $c[3] }}</td><td>{{ $c[4] }}</td><td>{{ $c[5] }}</td><td>{{ $c[6] }}</td>
      <td class="{{ str_starts_with($c[7], '+') ? 'pos' : 'neg' }}">{{ $c[7] }}</td>
      <td><span class="badge {{ $c[8] === 'Good Standing' ? 'b-good' : ($c[8] === 'Warning' ? 'b-warning' : 'b-probation') }}">{{ $c[8] }}</span></td>
      <td>
        @if ($canEdit)
          <button class="btn ghost" style="padding:3px 10px" onclick="removeRow(this)">Remove</button>
        @endif
      </td>
    </tr>
  @endforeach
  </tbody></table></div>

<dialog id="addDlg">
  <form id="addForm" method="dialog">
    <h3>Add new cadet</h3>
    <div class="alert note">Use the official roster format. The system checks required fields and duplicate serial numbers before saving.</div>
    <label class="f"><span>Full name</span><input id="a_name" placeholder="Surname, First Name M." required></label>
    <label class="f"><span>Serial number</span><input id="a_sn" placeholder="CE-R25-002444" required pattern="CE-[A-Z]\d{2}-\d{6}" title="Format: CE-R25-002444"></label>
    <label class="f"><span>Battalion</span><select id="a_bn" required><option value="">Select...</option><option>1st Bn</option><option>2nd Bn</option><option>3rd Bn</option></select></label>
    <label class="f"><span>Company</span><select id="a_co" required><option value="">Select...</option><option>Alpha Co</option><option>Bravo Co</option></select></label>
    <label class="f"><span>Platoon</span><select id="a_pl" required><option value="">Select...</option><option>Plt 1</option><option>Plt 2</option><option>Plt 3</option></select></label>
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
