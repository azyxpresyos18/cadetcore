@extends('layouts.app')
@section('role', 'adjutant')
@section('title', 'Yearly Update')
@section('heading', 'Prepare Yearly Update')
@section('subtitle', 'Controlled rollover workflow · Current AY 2026-2027')
@section('actions')<a class="btn ghost" href="{{ route('adjutant.years') }}">&#128274; View Academic Years</a>@endsection
@section('content')
@php
  // SAMPLE DATA: replace with database queries later.
  $officers = [['Santos, Maria L.','CE-R23-000101','Platoon Leader','1st Bn'],['Dela Cruz, Pedro','CE-R23-000102','Battalion S1','1st Bn'],['Ramos, Luis','CE-R24-000103','Platoon Leader','2nd Bn'],['Garcia, Ana','CE-R24-000104','Battalion S1','3rd Bn']];
@endphp
<div class="y-banner"><strong>Records are never deleted during rollover.</strong>
  <p>Graduating cadets and outgoing officers are moved to the read-only archive with their attendance, merit, demerit, and audit history intact.</p></div>

<div class="y-wrap">
  <ol class="y-steps" id="ySteps">
    <li data-s="1" class="on"><i>1</i><div><b>Review graduating records</b><small id="sub1">49 records identified</small></div></li>
    <li data-s="2"><i>2</i><div><b>Import new cadets</b><small id="sub2">Add cadets and validate</small></div></li>
    <li data-s="3"><i>3</i><div><b>Update officer accounts</b><small>Assign roles and unit access</small></div></li>
    <li data-s="4"><i>4</i><div><b>Start academic year</b><small>Confirm and activate</small></div></li>
  </ol>

  <div class="y-panel">
    {{-- STEP 1 --}}
    <section data-step="1">
      <h3>Archive graduating cadets and officers</h3>
      <p class="y-hint">Review the records identified for AY 2026-2027 before archiving.</p>
      <div class="y-check">
        <label><input type="checkbox" class="grad" data-n="18" checked><span>18 graduating regular cadets</span><em>Ready</em></label>
        <label><input type="checkbox" class="grad" data-n="25" checked><span>25 graduating aspiring officers</span><em>Ready</em></label>
        <label><input type="checkbox" class="grad" data-n="6" checked><span>6 outgoing officer accounts</span><em>Ready</em></label>
      </div>
      <div id="e1"></div>
      <div class="y-actions"><button class="btn ghost" disabled>Back</button><button class="btn" onclick="next(1)">Save &amp; continue</button></div>
    </section>

    {{-- STEP 2: same fields as "Add New Cadet" --}}
    <section data-step="2" class="hide">
      <h3>Import new cadets</h3>
      <p class="y-hint">Add each incoming cadet using the official roster format. The system checks required fields and duplicate serial numbers before staging.</p>
      <form id="addForm" class="y-form">
        <label class="f"><span>Full name</span><input id="a_name" placeholder="Surname, First Name M." required></label>
        <label class="f"><span>Serial number</span><input id="a_sn" placeholder="CE-R25-002444" required pattern="CE-[A-Z]\d{2}-\d{6}" title="Format: CE-R25-002444"></label>
        <label class="f"><span>Battalion</span><select id="a_bn" required><option value="">Select...</option><option>1st Bn</option><option>2nd Bn</option><option>3rd Bn</option></select></label>
        <label class="f"><span>Company</span><select id="a_co" required><option value="">Select...</option><option>Alpha Co</option><option>Bravo Co</option></select></label>
        <label class="f"><span>Platoon</span><select id="a_pl" required><option value="">Select...</option><option>Plt 1</option><option>Plt 2</option><option>Plt 3</option></select></label>
        <label class="f"><span>Course and year</span><input id="a_cy" placeholder="e.g. BSIT-1" required></label>
        <div class="full"><button class="btn" type="submit">Validate &amp; add cadet</button></div>
      </form>
      <div id="e2"></div>
      <div class="tablewrap" id="stageWrap" style="display:none"><table id="stage">
        <thead><tr><th>Serial No.</th><th>Name</th><th>Bn</th><th>Co</th><th>Plt</th><th>Course / Year</th><th></th></tr></thead><tbody></tbody></table></div>
      <div class="y-actions"><button class="btn ghost" onclick="go(1)">Back</button><button class="btn" onclick="next(2)">Save &amp; continue</button></div>
    </section>

    {{-- STEP 3 --}}
    <section data-step="3" class="hide">
      <h3>Update officer accounts</h3>
      <p class="y-hint">Keep, replace or deactivate current officers, and add new officer accounts for the incoming year.</p>
      <div class="tablewrap"><table id="offTbl"><thead><tr><th>Name</th><th>Serial No.</th><th>Position</th><th>Unit</th><th>Action</th></tr></thead><tbody>
      @foreach ($officers as $o)
        <tr><td class="oname" data-orig="{{ $o[0] }}">{{ $o[0] }}</td><td class="osn" data-orig="{{ $o[1] }}">{{ $o[1] }}</td><td>{{ $o[2] }}</td><td>{{ $o[3] }}</td><td><select class="offAct" style="width:auto"><option>Keep</option><option>Replace</option><option>Deactivate</option></select></td></tr>
      @endforeach</tbody></table></div>
      <button class="btn ghost" style="margin:12px 0" onclick="officerDlg.showModal()">+ Add officer account</button>
      <div id="e3"></div>
      <div class="y-actions"><button class="btn ghost" onclick="go(2)">Back</button><button class="btn" onclick="next(3)">Save &amp; continue</button></div>
    </section>

    {{-- STEP 4 --}}
    <section data-step="4" class="hide">
      <h3>Start academic year</h3>
      <p class="y-hint">The previous year is archived (read-only) and the new year becomes the active year.</p>
      <div class="y-new"><small>NEW ACADEMIC YEAR</small><div class="big">AY 2027-2028 · Semester 1</div><p id="sum"></p></div>
      <div id="e4"></div>
      <div class="y-actions"><button class="btn ghost" onclick="go(3)">Back</button><button class="btn" onclick="confirmYear()">Confirm yearly update</button></div>
    </section>
  </div>
</div>

<dialog id="officerDlg">
  <form id="officerForm" method="dialog">
    <h3>Add officer account</h3>
    <label class="f"><span>Full name</span><input id="o_name" placeholder="Surname, First Name M." required></label>
    <label class="f"><span>Serial number</span><input id="o_sn" pattern="CE-[A-Z]\d{2}-\d{6}" title="Format: CE-R25-002444" placeholder="CE-R25-002444" required></label>
    <label class="f"><span>Position</span><select id="o_pos" required><option value="">Select...</option><option>Platoon Leader</option><option>Battalion S1</option><option>Corps S1</option><option>Corps Adjutant</option></select></label>
    <label class="f hide" id="w_bn"><span>Battalion</span><select id="o_bn"><option value="">Select...</option><option>1st Bn</option><option>2nd Bn</option><option>3rd Bn</option></select></label>
    <label class="f hide" id="w_co"><span>Company</span><select id="o_co"><option value="">Select...</option><option>Alpha Co</option><option>Bravo Co</option></select></label>
    <label class="f hide" id="w_pl"><span>Platoon</span><select id="o_pl"><option value="">Select...</option><option>1st Plt</option><option>2nd Plt</option><option>3rd Plt</option></select></label>
    <div class="dlg-actions"><button type="button" class="btn ghost" style="color:var(--ink);border-color:var(--ink)" onclick="officerDlg.close()">Cancel</button><button class="btn" value="save">Add officer</button></div>
  </form>
</dialog>

<dialog id="replaceDlg">
  <form id="replaceForm" method="dialog">
    <h3>Appoint replacement</h3>
    <p style="margin:0 0 12px" id="rep_info"></p>
    <label class="f"><span>Name of the new appointee</span><input id="rep_name" placeholder="Surname, First Name M." required></label>
    <label class="f"><span>Serial number</span><input id="rep_sn" pattern="CE-[A-Z]\d{2}-\d{6}" title="Format: CE-R25-002444" placeholder="CE-R25-002444" required></label>
    <div id="rep_err"></div>
    <div class="dlg-actions"><button type="button" class="btn ghost" style="color:var(--ink);border-color:var(--ink)" id="repCancel">Cancel</button><button class="btn" value="save">Confirm replacement</button></div>
  </form>
</dialog>

<dialog id="doneDlg">
  <h3>Yearly update ready</h3>
  <div class="alert ok">AY 2027-2028 has been prepared.<br><small style="font-weight:400">The prior year is archived, new cadets are staged, and role-based officer accounts are ready.</small></div>
  <div class="dlg-actions"><a class="btn" href="{{ route('adjutant.years') }}">View Academic Years</a><button class="btn ghost" style="color:var(--ink);border-color:var(--ink)" onclick="doneDlg.close()">Done</button></div>
</dialog>
@endsection
@push('scripts')
<script>
const $ = id => document.getElementById(id);
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let cur = 1, newOfficers = [], staged = [], finished = false;
function go(n) {
  cur = n;
  document.querySelectorAll('[data-step]').forEach(c => c.classList.toggle('hide', c.dataset.step != n));
  document.querySelectorAll('#ySteps li').forEach(li => li.classList.toggle('on', +li.dataset.s === n));
  if (n === 4) summary();
}
const gradCount = () => [...document.querySelectorAll('.grad:checked')].reduce((t, c) => t + +c.dataset.n, 0);
function next(from) {
  if (from === 1) {
    if (!document.querySelector('.grad:checked')) { $('e1').innerHTML = '<div class="alert err">Select at least one group to archive.</div>'; return; }
    $('e1').innerHTML = ''; $('sub1').textContent = gradCount() + ' records identified';
  }
  go(from + 1);
}
document.querySelectorAll('#ySteps li').forEach(li => li.onclick = () => { if (+li.dataset.s < cur) go(+li.dataset.s); });

/* step 2 */
function renderStage() {
  $('stageWrap').style.display = staged.length ? '' : 'none';
  $('stage').querySelector('tbody').innerHTML = staged.map((c, i) => `<tr><td>${esc(c.sn)}</td><td>${esc(c.name)}</td><td>${c.bn}</td><td>${c.co}</td><td>${c.pl}</td><td>${esc(c.cy)}</td><td><button class="btn ghost" style="padding:3px 10px" onclick="unstage(${i})">Remove</button></td></tr>`).join('');
  $('sub2').textContent = staged.length ? staged.length + ' new cadet' + (staged.length > 1 ? 's' : '') + ' staged' : 'Add cadets and validate';
}
window.unstage = i => { staged.splice(i, 1); renderStage(); };
$('addForm').addEventListener('submit', e => {
  e.preventDefault();
  const sn = a_sn.value.trim();
  if (staged.some(c => c.sn === sn)) { $('e2').innerHTML = '<div class="alert err">Duplicate serial number: this cadet is already staged.</div>'; return; }
  // DEMO ONLY: also check against existing cadet records on the server.
  staged.push({sn, name: a_name.value.trim(), bn: a_bn.value, co: a_co.value, pl: a_pl.value, cy: a_cy.value.trim()});
  $('e2').innerHTML = `<div class="alert ok">Cadet ${esc(a_name.value)} was added successfully.</div>`;
  e.target.reset(); renderStage();
});

/* step 3 */
// Which extra fields each position needs
const needs = {'Platoon Leader': ['w_bn','w_co','w_pl'], 'Battalion S1': ['w_bn'], 'Corps S1': [], 'Corps Adjutant': []};
function syncPos() {
  const show = needs[$('o_pos').value] || [];
  ['w_bn','w_co','w_pl'].forEach(id => {
    const on = show.includes(id), box = $(id);
    box.classList.toggle('hide', !on); box.querySelector('select').required = on;
    if (!on) box.querySelector('select').value = '';
  });
}
$('o_pos').onchange = syncPos;
$('officerForm').addEventListener('submit', e => {
  if (e.submitter && e.submitter.value !== 'save') return;
  const pos = o_pos.value, unit = [o_bn.value, o_co.value, o_pl.value].filter(Boolean);
  const sn = o_sn.value.trim();
  if (snTaken(sn)) { e.preventDefault(); alert('Duplicate serial number: this serial number is already used in this update.'); return; }
  const o = {name: o_name.value.trim(), sn, pos, unit: unit.join(' · ') || 'Corps level'}; newOfficers.push(o);
  $('offTbl').querySelector('tbody').insertAdjacentHTML('beforeend', `<tr><td>${esc(o.name)} <span class="badge b-good">New</span></td><td>${esc(sn)}</td><td>${pos}</td><td>${esc(o.unit)}</td><td>Add</td></tr>`);
  $('officerForm').reset(); syncPos();
});

// serial numbers already used by existing officers, staged cadets or newly added officers
const snTaken = sn => staged.some(c => c.sn === sn) || newOfficers.some(o => o.sn === sn)
  || [...document.querySelectorAll('#offTbl .osn')].some(td => td.textContent.trim() === sn && td.dataset.cur !== 'replaced');
// Replace: ask who is being appointed to that position
let repSel = null;
function restore(tr) { const c = tr.querySelector('.oname'), n = tr.querySelector('.osn'); c.textContent = c.dataset.orig; n.textContent = n.dataset.orig; n.dataset.cur = ''; }
function revert(sel) { sel.value = 'Keep'; restore(sel.closest('tr')); sel.dataset.prev = 'Keep'; }
document.querySelectorAll('.offAct').forEach(sel => {
  sel.dataset.prev = 'Keep';
  sel.onchange = () => {
    const tr = sel.closest('tr'), c = tr.querySelector('.oname');
    if (sel.value === 'Replace') {
      repSel = sel; rep_name.value = '';
      $('rep_err').innerHTML = ''; rep_info.textContent = `Position: ${tr.children[2].textContent} (${tr.children[3].textContent}). Current officer: ${c.dataset.orig}.`;
      replaceDlg.showModal();
    } else { restore(tr); sel.dataset.prev = sel.value; }
  };
});
$('repCancel').onclick = () => { if (repSel) revert(repSel); replaceDlg.close(); };
replaceDlg.addEventListener('cancel', () => { if (repSel) revert(repSel); });
$('replaceForm').addEventListener('submit', e => {
  if (e.submitter && e.submitter.value !== 'save') return;
  const tr = repSel.closest('tr'), c = tr.querySelector('.oname'), n = tr.querySelector('.osn'), sn = rep_sn.value.trim();
  if (snTaken(sn)) { e.preventDefault(); $('rep_err').innerHTML = '<div class="alert err">Duplicate serial number: already used in this update.</div>'; return; }
  c.innerHTML = `${esc(rep_name.value.trim())} <span class="badge b-good">New</span><br><small style="opacity:.75">replaces ${esc(c.dataset.orig)}</small>`;
  n.textContent = sn; n.dataset.cur = 'replaced';
  repSel.dataset.prev = 'Replace'; repSel = null; $('replaceForm').reset();
});

/* step 4 */
function summary() {
  const deact = [...document.querySelectorAll('.offAct')].filter(s => s.value !== 'Keep').length;
  $('sum').textContent = `${gradCount()} records will be archived · ${staged.length} new cadets · ${newOfficers.length} new officer accounts · ${deact} existing officer accounts changed.`;
}
function confirmYear() {
  if (finished) return;
  // DEMO ONLY: POST the whole rollover (archive + staged cadets + officer changes) in one transaction.
  finished = true; doneDlg.showModal();
  document.querySelector('[data-step="4"] .y-actions .btn:not(.ghost)').textContent = 'Yearly update confirmed';
}
</script>
@endpush
