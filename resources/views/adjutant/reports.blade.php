@extends('layouts.app')
@section('role', 'adjutant')
@section('title', 'Reports')
@section('heading', 'Reports & Analytics')
@section('subtitle', 'All battalions · AY 2026-2027 Semester 1')
@section('actions')<button class="btn gen" onclick="openGen()">&#10515; Generate Report</button>@endsection
@section('content')
@php
  // SAMPLE DATA: replace with database queries later.
  $bns = [['1st Battalion',92,58,22],['2nd Battalion',81,61,38],['3rd Battalion',75,37,29]];
  $top = [['Torres, Diana M.','3rd Bn · 3rd Platoon','+28'],['Mendoza, Carlo T.','1st Bn · 1st Platoon','+28'],['Santos, Maria L.','1st Bn · 1st Platoon','+28']];
  $low = [['Lim, Kevin D.','3rd Bn · 3rd Platoon','-13'],['Garcia, Jose R.','2nd Bn · 2nd Platoon','-2']];
@endphp
<div id="out"></div>
<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(200px,260px))">
  <div class="stat"><div class="label">Unit strength</div><div class="num">248</div><small>3 battalions</small></div>
  <div class="stat"><div class="label">Attendance summary</div><div class="num">94%</div><small>Current semester average</small></div>
</div>
<div class="rep-grid">
  <div class="rep-card">
    <h3>Battalion Merit/Demerit Comparison</h3>
    @foreach ($bns as [$n, $c, $m, $d])
      <div class="rep-bn"><div class="rep-row"><b>{{ $n }}</b><span>{{ $c }} cadets</span></div>
        <div class="rep-bar"><i class="m" style="flex:{{ $m }}"></i><i class="d" style="flex:{{ $d }}"></i></div>
        <div class="rep-leg"><span><u class="m"></u>Merits: {{ $m }}</span><span><u class="d"></u>Demerits: {{ $d }}</span></div></div>
    @endforeach
  </div>
  <div class="rep-card">
    <h3>Cadet Standing — Top &amp; Bottom</h3>
    <div class="rep-sec">Top performers</div>
    @foreach ($top as $i => [$n, $u, $p])<div class="rep-person good"><span class="rk">{{ $i + 1 }}</span><div><b>{{ $n }}</b><small>{{ $u }}</small></div><em>{{ $p }}</em></div>@endforeach
    <div class="rep-sec bad">Needs improvement</div>
    @foreach ($low as [$n, $u, $p])<div class="rep-person low"><span class="rk">!</span><div><b>{{ $n }}</b><small>{{ $u }}</small></div><em>{{ $p }}</em></div>@endforeach
  </div>
</div>

<dialog id="genDlg">
  <form id="rf" method="dialog">
    <div style="display:flex;justify-content:space-between;align-items:center"><h3>Generate Report</h3><button type="button" class="x" onclick="genDlg.close()" aria-label="Close">&times;</button></div>
    <div id="derr"></div>
    <label class="f"><span>Report type</span><select id="r_type" required><option value="">Select...</option><option>Battalion strength</option><option>Merit / demerit analysis</option><option>Attendance summary</option><option>Cadet entry history</option></select></label>
    <label class="f"><span>Battalion</span><select id="r_bn"><option>All battalions</option><option>1st Battalion</option><option>2nd Battalion</option><option>3rd Battalion</option></select></label>
    <label class="f"><span>From</span><input type="date" id="r_from" required></label>
    <label class="f"><span>To</span><input type="date" id="r_to" required></label>
    <label class="f"><span>Format</span><select id="r_fmt"><option>PDF</option><option>Excel</option></select></label>
    <div class="dlg-actions"><button type="button" class="btn ghost" style="color:var(--ink);border-color:var(--ink)" onclick="genDlg.close()">Cancel</button><button class="btn" value="save">Generate report</button></div>
  </form>
</dialog>
@endsection
@push('scripts')
<script>
const $ = id => document.getElementById(id);
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
window.openGen = () => { $('derr').innerHTML = ''; genDlg.showModal(); };
$('rf').addEventListener('submit', e => {
  if (e.submitter && e.submitter.value !== 'save') return;
  if ($('r_to').value < $('r_from').value) { e.preventDefault(); $('derr').innerHTML = '<div class="alert err">The end date must be after the start date.</div>'; return; }
  // DEMO ONLY: call the real report export here.
  $('out').innerHTML = `<div class="alert ok rep-ok"><span>Report prepared successfully: ${esc($('r_type').value)} (${esc($('r_bn').value)}, ${$('r_from').value} to ${$('r_to').value}).
    <a href="#" onclick="alert('Connect this button to your report export later.');return false">Download ${$('r_fmt').value}</a></span>
    <a href="#" onclick="document.getElementById('out').innerHTML='';return false">Dismiss</a></div>`;
  $('rf').reset();
});
</script>
@endpush
