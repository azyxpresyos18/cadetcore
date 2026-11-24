@extends('layouts.app')
@section('role', 'adjutant')
@section('title', 'Officers')
@section('heading', 'Officers')
@section('subtitle', count($officers).' '.\Illuminate\Support\Str::plural('officer', count($officers)).($yearLabel ? ' · AY '.$yearLabel : ''))
@section('actions')
  <button class="btn" onclick="openAdd()">+ Add New Officer</button>
@endsection
@section('content')
<div id="msg"></div>
<div class="tablewrap"><table>
  <thead><tr><th>Serial No.</th><th>Name</th><th>Position</th><th>Assigned unit</th><th>Course / Year</th><th>Account</th><th></th></tr></thead>
  <tbody>
  @forelse ($officers as $o)
    <tr data-e="{{ json_encode($o[8] ?? []) }}" data-id="{{ $o[6] }}">
      <td>{{ $o[0] }}</td><td>{{ $o[1] }}</td><td>{{ $o[2] }}</td><td>{{ $o[3] }}</td><td>{{ $o[4] }}</td>
      <td><span class="badge {{ $o[5] === 'Active' ? 'b-good' : 'b-neutral' }}">{{ $o[5] }}</span></td>
      <td>
        @if (($o[7] ?? '') !== 'adjutant')
          <button class="btn ghost" style="padding:3px 10px" onclick="openEdit(this)">Edit</button>
          <button class="btn ghost" style="padding:3px 10px" onclick="removeRow(this)">Remove</button>
        @endif
      </td>
    </tr>
  @empty
    <tr><td colspan="7">No officers are assigned for the current academic year.</td></tr>
  @endforelse
  </tbody></table></div>

<dialog id="offDlg">
  <form id="offForm" method="dialog">
    <h3 id="offTitle">Add new officer</h3>
    <div id="offErr"></div>
    <label class="f"><span>Last name</span><input id="o_ln" maxlength="100" placeholder="Dela Cruz" required></label>
    <label class="f"><span>First name</span><input id="o_fn" maxlength="100" placeholder="Juan" required></label>
    <label class="f"><span>Middle name <small>(optional)</small></span><input id="o_mn" maxlength="100" placeholder="Santos"></label>
    <label class="f"><span>Serial number</span><input id="o_sn" placeholder="CE-R25-002444" required pattern="CE-[A-Z]\d{2}-\d{6}" title="Format: CE-R25-002444"></label>
    <label class="f"><span>Email <small>(optional, used for sign-in and password reset)</small></span><input type="email" id="o_em" maxlength="150" placeholder="name@example.com"></label>
    <label class="f"><span>Position</span>
      <select id="o_pos" required>
        <option value="">Select...</option>
        <option>Corps S1</option><option>Battalion S1</option><option>Platoon Leader</option>
      </select>
    </label>
    <label class="f hide" id="g_bn"><span>Battalion</span><select id="o_bn"></select></label>
    <label class="f hide" id="g_co"><span>Company</span><select id="o_co"></select></label>
    <label class="f hide" id="g_pl"><span>Platoon</span><select id="o_pl"></select></label>
    <div class="alert note hide" id="g_na">Corps S1 is not tied to a unit, so the assigned unit will be N/A.</div>
    <label class="f"><span>Course and year level</span><input id="o_cy" maxlength="150" placeholder="e.g. Bachelor of Science in Information Systems-1" required></label>
    <div class="dlg-actions"><button type="button" class="btn ghost" style="color:var(--ink);border-color:var(--ink)" onclick="offDlg.close()">Cancel</button><button class="btn" value="save" id="offSubmit">Add officer</button></div>
  </form>
</dialog>

<dialog id="doneDlg">
  <h3>Officer added</h3>
  <div class="alert ok" id="doneMsg"></div>
  <p>Give these sign-in details to the officer. The password is shown only once.</p>
  <p><strong>Serial number:</strong> <span id="doneSn"></span><br><strong>Temporary password:</strong> <span id="donePw" style="font-family:'Space Mono',monospace"></span></p>
  <div class="dlg-actions"><button type="button" class="btn" onclick="location.reload()">Done</button></div>
</dialog>
@endsection
@push('scripts')
@include('partials.cascade')
<script>
const BASE = @json(url()->current());
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
cascade(o_bn, o_co, o_pl, @json($structure ?? []));

// Show only the unit dropdowns the chosen position needs.
function showUnits() {
  const p = o_pos.value, pl = p === 'Platoon Leader', bn = pl || p === 'Battalion S1';
  g_bn.classList.toggle('hide', !bn);
  g_co.classList.toggle('hide', !pl);
  g_pl.classList.toggle('hide', !pl);
  g_na.classList.toggle('hide', p !== 'Corps S1');
  o_bn.required = bn; o_co.required = pl; o_pl.required = pl;
}
o_pos.addEventListener('change', showUnits);
offForm.addEventListener('reset', () => setTimeout(showUnits, 0));

let editId = null;
function openAdd() {
  editId = null; offForm.reset(); offErr.innerHTML = '';
  offTitle.textContent = 'Add new officer'; offSubmit.textContent = 'Add officer';
  offDlg.showModal();
}
function openEdit(b) {
  const tr = b.closest('tr'), d = JSON.parse(tr.dataset.e || '{}');
  editId = tr.dataset.id; offErr.innerHTML = '';
  offTitle.textContent = 'Edit officer'; offSubmit.textContent = 'Save changes';
  o_ln.value = d.last || ''; o_fn.value = d.first || ''; o_mn.value = d.middle || '';
  o_sn.value = d.serial || ''; o_em.value = d.email || ''; o_cy.value = d.course || '';
  o_pos.value = d.pos || ''; showUnits();
  o_bn.value = d.bn || ''; o_bn.dispatchEvent(new Event('change'));
  o_co.value = d.co || ''; o_co.dispatchEvent(new Event('change'));
  o_pl.value = d.pl || '';
  offDlg.showModal();
}
async function removeRow(b) {
  if (!confirm('Remove this officer? Their position is cleared and their login is switched off. Their records are kept.')) return;
  try { await api('DELETE', `${BASE}/${b.closest('tr').dataset.id}`); location.reload(); }
  catch (err) { document.getElementById('msg').innerHTML = `<div class="alert err">${esc(err.message)}</div>`; }
}

offForm.addEventListener('submit', async e => {
  if (e.submitter && e.submitter.value !== 'save') return;
  e.preventDefault();
  offErr.innerHTML = '';
  const p = o_pos.value;
  const body = {
    last_name: o_ln.value.trim(), first_name: o_fn.value.trim(), middle_name: o_mn.value.trim(),
    serial: o_sn.value.trim(), email: o_em.value.trim(), position: p, course: o_cy.value.trim(),
    battalion: p === 'Corps S1' ? '' : o_bn.value,
    company: p === 'Platoon Leader' ? o_co.value : '',
    platoon: p === 'Platoon Leader' ? o_pl.value : '',
  };
  try {
    if (editId) { await api('PUT', `${BASE}/${editId}`, body); location.reload(); return; }
    const res = await api('POST', BASE, body);
    offForm.reset(); offDlg.close();
    doneMsg.textContent = res.message; doneSn.textContent = res.serial; donePw.textContent = res.password;
    doneDlg.showModal();
  } catch (err) { offErr.innerHTML = `<div class="alert err">${esc(err.message)}</div>`; }
});
</script>
@endpush
