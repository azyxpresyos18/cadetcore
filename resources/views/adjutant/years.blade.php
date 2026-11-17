@extends('layouts.app')
@section('role', 'adjutant')
@section('title', 'Academic Years')
@section('heading', 'Academic Years')
@section('subtitle', 'Read-only archive of previous academic years and their records')
@section('actions')<a class="btn ghost" href="{{ route('adjutant.yearly') }}">&larr; Back to Yearly Update</a>@endsection
@section('content')
@php
  // SAMPLE DATA: replace with database queries later.
  $years = [
    '2025-2026' => ['cadets'=>236,'officers'=>41,'att'=>92,'merits'=>412,'demerits'=>187,'archived'=>'2026-06-05',
      'records'=>[['CE-R22-000004','Villanueva, Paolo R.','1st Bn','Graduated','+34','95%'],['CE-R22-000019','Aquino, Bea T.','2nd Bn','Graduated','+27','97%'],['CE-R23-000042','Mercado, Dan K.','3rd Bn','Graduated','+9','88%'],['CE-R23-000066','Flores, Ian S.','1st Bn','Graduated','-4','81%'],['CE-R22-000003','Navarro, Carla P.','2nd Bn','Outgoing officer','+41','98%']]],
    '2024-2025' => ['cadets'=>221,'officers'=>38,'att'=>90,'merits'=>366,'demerits'=>204,'archived'=>'2025-06-06',
      'records'=>[['CE-R21-000008','Bautista, Leo M.','1st Bn','Graduated','+22','93%'],['CE-R21-000031','Castillo, Mia J.','3rd Bn','Graduated','+30','96%'],['CE-R22-000050','Ocampo, Rey D.','2nd Bn','Graduated','-7','79%'],['CE-R21-000002','Soriano, Hanna V.','1st Bn','Outgoing officer','+38','97%']]],
    '2023-2024' => ['cadets'=>208,'officers'=>36,'att'=>89,'merits'=>331,'demerits'=>219,'archived'=>'2024-06-07',
      'records'=>[['CE-R20-000011','Padilla, Jon C.','2nd Bn','Graduated','+18','90%'],['CE-R20-000027','Estrada, Lia F.','1st Bn','Graduated','+25','94%'],['CE-R20-000001','Mendoza, Rico A.','3rd Bn','Outgoing officer','+36','96%']]],
  ];
@endphp
<div class="alert note">&#128274; Archived years are read-only. Records cannot be edited or deleted; attendance, merit, demerit and audit history are kept intact.</div>
<div class="filters" id="ayPills">
  @foreach (array_keys($years) as $i => $ay)<button class="pill {{ $i === 0 ? 'on' : '' }}" data-ay="{{ $ay }}">AY {{ $ay }}</button>@endforeach
</div>
<div class="stats" id="ayStats"></div>
<div class="filters"><input id="q" placeholder="Search name or serial no..."></div>
<div class="tablewrap"><table id="tbl">
  <thead><tr><th>Serial No.</th><th>Name</th><th>Battalion</th><th>Status</th><th>Final net points</th><th>Attendance</th></tr></thead><tbody></tbody></table></div>
@endsection
@push('scripts')
<script>
const YEARS = @json($years);
const $ = id => document.getElementById(id);
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let ay = Object.keys(YEARS)[0];
function draw() {
  const y = YEARS[ay], q = $('q').value.toLowerCase();
  $('ayStats').innerHTML = [['Cadets enrolled', y.cadets, 'AY ' + ay], ['Officer accounts', y.officers, 'Archived ' + y.archived], ['Average attendance', y.att + '%', 'Full year'], ['Merits / demerits', y.merits + ' / ' + y.demerits, 'Total entries']]
    .map(s => `<div class="stat"><div class="label">${s[0]}</div><div class="num">${s[1]}</div><small>${s[2]}</small></div>`).join('');
  $('tbl').querySelector('tbody').innerHTML = y.records.filter(r => r.join(' ').toLowerCase().includes(q)).map(r =>
    `<tr><td>${esc(r[0])}</td><td>${esc(r[1])}</td><td>${r[2]}</td><td><span class="badge b-neutral">${r[3]}</span></td><td class="${r[4].startsWith('+') ? 'pos' : 'neg'}">${r[4]}</td><td>${r[5]}</td></tr>`).join('')
    || '<tr><td colspan="6">No records match.</td></tr>';
}
document.querySelectorAll('#ayPills .pill').forEach(p => p.onclick = () => { document.querySelectorAll('#ayPills .pill').forEach(x => x.classList.remove('on')); p.classList.add('on'); ay = p.dataset.ay; draw(); });
$('q').oninput = draw; draw();
</script>
@endpush
