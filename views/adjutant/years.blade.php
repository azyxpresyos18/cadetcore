@extends('layouts.app')
@php
  // Archived academic years. The backend passes these; nothing is hardcoded here.
  $years : ['2025-2026' => ['cadets'=>n,'officers'=>n,'att'=>percent,'merits'=>n,'demerits'=>n,'archived'=>'Y-m-d',
            'records'=>[[serial, name, battalion, status, signed net, '95%'], ...]], ...]
  $years = $years ?? [];
@endphp
@section('role', 'adjutant')
@section('title', 'Academic Years')
@section('heading', 'Academic Years')
@section('subtitle', 'Read-only archive of previous academic years and their records')
@section('actions')<a class="btn ghost" href="{{ route('adjutant.yearly') }}">&larr; Back to Yearly Update</a>@endsection
@section('content')
@if (! count($years))
<div class="alert note">No archived academic years yet. A year appears here after it has been closed through the Yearly Update.</div>
@else
<div class="alert note">&#128274; Archived years are read-only. Records cannot be edited or deleted; attendance, merit, demerit and audit history are kept intact.</div>
<div class="filters" id="ayPills">
  @foreach (array_keys($years) as $i => $ay)<button class="pill {{ $i === 0 ? 'on' : '' }}" data-ay="{{ $ay }}">AY {{ $ay }}</button>@endforeach
</div>
<div class="stats" id="ayStats"></div>
<div class="filters"><input id="q" placeholder="Search name or serial no..."></div>
<div class="tablewrap"><table id="tbl">
  <thead><tr><th>Serial No.</th><th>Name</th><th>Battalion</th><th>Status</th><th>Final net points</th><th>Attendance</th></tr></thead><tbody></tbody></table></div>
@endif
@endsection
@if (count($years))
@push('scripts')
<script>
const YEARS = @json($years);
const $ = id => document.getElementById(id);
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let ay = Object.keys(YEARS)[0];
function draw() {
  const y = YEARS[ay], q = $('q').value.toLowerCase();
  $('ayStats').innerHTML = [['Cadets enrolled', y.cadets, 'AY ' + ay], ['Officer accounts', y.officers, 'Ended ' + y.archived], ['Average attendance', y.att + '%', 'Full year'], ['Merits / demerits', y.merits + ' / ' + y.demerits, 'Total entries']]
    .map(s => `<div class="stat"><div class="label">${s[0]}</div><div class="num">${s[1]}</div><small>${s[2]}</small></div>`).join('');
  $('tbl').querySelector('tbody').innerHTML = y.records.filter(r => r.join(' ').toLowerCase().includes(q)).map(r =>
    `<tr><td>${esc(r[0])}</td><td>${esc(r[1])}</td><td>${r[2]}</td><td><span class="badge b-neutral">${r[3]}</span></td><td class="${r[4].startsWith('+') ? 'pos' : 'neg'}">${r[4]}</td><td>${r[5]}</td></tr>`).join('')
    || '<tr><td colspan="6">No records match.</td></tr>';
}
document.querySelectorAll('#ayPills .pill').forEach(p => p.onclick = () => { document.querySelectorAll('#ayPills .pill').forEach(x => x.classList.remove('on')); p.classList.add('on'); ay = p.dataset.ay; draw(); });
$('q').oninput = draw; draw();
</script>
@endpush
@endif
