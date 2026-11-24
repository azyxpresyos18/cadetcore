@extends('layouts.app')
@php
  // Platoon attendance data. The backend passes these; nothing is hardcoded here.
  // $cadets   : rows of [serial no, name]
  // $sessions : rows of [session id, 'Title · 13 Sep 2026']
  $cadets = $cadets ?? [];
  $sessions = $sessions ?? [];
@endphp
@section('role', 'platoon_leader')
@section('title', 'Attendance')
@section('heading', 'Attendance')
@section('subtitle', 'Select a session, mark attendance, then save')
@section('content')
<div id="msg"></div>
<div class="card">
  <div class="filters">
    <select id="sess"><option value="">Select training session...</option>@foreach ($sessions as $s)<option value="{{ $s[0] }}">{{ $s[1] }}</option>@endforeach</select>
    <label style="display:flex;gap:6px;align-items:center;font-weight:600"><input type="checkbox" id="sub" style="width:auto"> Substitute officer mode</label>
  </div>
  <div id="subbox" class="hide">
    <div class="alert note">Entries made in this mode stay in the same platoon record, with the substitute officer's name attached.</div>
    <label class="f"><span>Substitute officer name</span><input id="subname" placeholder="Full name"></label>
  </div>
</div>
<div class="tablewrap"><table id="tbl">
  <thead><tr><th>Serial No.</th><th>Cadet</th><th>Status</th><th>Time in</th><th>Remark</th></tr></thead>
  <tbody>
  @forelse ($cadets as $c)
    <tr><td>{{ $c[0] }}</td><td>{{ $c[1] }}</td>
      <td><select class="st" style="width:auto"><option value="">Select</option><option>Present</option><option>Late</option><option>Excused</option><option>Absent</option></select></td>
      <td><input type="time" class="ti" style="width:auto"></td>
      <td><input class="rm" placeholder="Optional remark"></td></tr>
  @empty
    <tr><td colspan="5">No cadets are assigned to your platoon.</td></tr>
  @endforelse
  </tbody></table></div>
<br>
<button class="btn ghost" id="allp">Mark all present</button>
<button class="btn" id="save">Save attendance</button>
@endsection
@push('scripts')
<script>
const $ = id => document.getElementById(id);
const ME = @json(session('display_name'));
$('sub').onchange = () => $('subbox').classList.toggle('hide', !$('sub').checked);
$('allp').onclick = () => document.querySelectorAll('.st').forEach(s => s.value = 'Present');
const BASE = @json(url()->current());
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const dataRows = () => [...document.querySelectorAll('#tbl tbody tr')].filter(tr => tr.querySelector('.st'));

// Choosing a session loads whatever was already saved for it, so nothing is overwritten blindly.
$('sess').onchange = async () => {
  dataRows().forEach(tr => { tr.querySelector('.st').value = ''; tr.querySelector('.ti').value = ''; tr.querySelector('.rm').value = ''; });
  if (!$('sess').value) return;
  try {
    const saved = await api('GET', `${BASE}/${$('sess').value}/records`);
    dataRows().forEach(tr => {
      const r = saved[tr.children[0].innerText.trim()];
      if (r) { tr.querySelector('.st').value = r.status; tr.querySelector('.ti').value = r.time_in; tr.querySelector('.rm').value = r.remark; }
    });
  } catch (err) { $('msg').innerHTML = `<div class="alert err">${esc(err.message)}</div>`; }
};

$('save').onclick = async () => {
  const m = $('msg');
  if (!$('sess').value) { m.innerHTML = '<div class="alert err">Select a training session first.</div>'; return; }
  if ($('sub').checked && !$('subname').value.trim()) { m.innerHTML = '<div class="alert err">Enter the substitute officer\'s name.</div>'; return; }
  const rows = dataRows();
  if (!rows.length) { m.innerHTML = '<div class="alert err">There are no cadets to record.</div>'; return; }
  if (rows.some(tr => !tr.querySelector('.st').value)) { m.innerHTML = '<div class="alert err">Every cadet needs a status before saving.</div>'; return; }
  $('save').disabled = true;
  try {
    const res = await api('POST', BASE, {
      session_id: +$('sess').value,
      substitute: $('sub').checked ? $('subname').value.trim() : null,
      rows: rows.map(tr => ({serial: tr.children[0].innerText.trim(), status: tr.querySelector('.st').value, time_in: tr.querySelector('.ti').value || null, remark: tr.querySelector('.rm').value.trim() || null})),
    });
    m.innerHTML = `<div class="alert ok">${esc(res.message)}</div>`;
  } catch (err) { m.innerHTML = `<div class="alert err">${esc(err.message)}</div>`; }
  $('save').disabled = false;
  window.scrollTo({top: 0, behavior: 'smooth'});
};
</script>
@endpush
