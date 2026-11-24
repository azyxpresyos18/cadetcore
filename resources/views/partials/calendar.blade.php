{{-- Usage: @include('partials.calendar', ['editable' => true|false]); expects $sessions from the controller. --}}
@php
  // $sessions: rows of ['id','date'=>'Y-m-d','title','time'=>'06:00 - 11:00','location','audience','type'], passed by the backend.
  $sessions = $sessions ?? [];
@endphp
<div class="grid2">
  <div class="card">
    <div class="cal-head"><button id="prev">&#8249;</button><h3 id="monthLabel" style="margin:0"></h3><button id="next">&#8250;</button></div>
    <div class="cal" id="cal"></div>
    <div class="legend">
      <span><i class="t-drill"></i>Drill</span><span><i class="t-field"></i>Field Exercise</span><span><i class="t-physical"></i>Physical</span><span><i class="t-academic"></i>Academic</span><span><i class="t-admin"></i>Admin/Other</span>
    </div>
  </div>
  <div>
    <div class="card" id="detail"><h3>Select a date</h3><p style="color:var(--muted)">Click a day with a session to see its details.</p></div>
    <div class="card"><h3>Upcoming Sessions</h3><div id="upcoming"></div></div>
  </div>
</div>

@if ($editable)
<dialog id="sessDlg">
  <form method="dialog" id="sessForm">
    <h3 id="sessTitle">Add Training Session</h3>
    <label class="f"><span>Session title</span><input id="s_title" required></label>
    <label class="f"><span>Date</span><input type="date" id="s_date" required></label>
    <label class="f"><span>Time</span><input id="s_time" placeholder="06:00 - 11:00" required></label>
    <label class="f"><span>Location</span><input id="s_loc" required></label>
    <label class="f"><span>Type</span><input id="s_type" maxlength="40" required autocomplete="off"></label>
    <label class="f"><span>Audience</span><select id="s_aud"><option>All Cadets</option><option>Aspiring Officers</option><option>Regular Cadets</option></select></label>
    <div class="dlg-actions"><button type="button" class="btn ghost" style="color:var(--ink);border-color:var(--ink)" onclick="sessDlg.close()">Cancel</button><button class="btn" value="save">Save session</button></div>
  </form>
</dialog>
@endif

@push('scripts')
<script>
const EDITABLE = @json($editable);
let sessions = @json($sessions);
let view = new Date(); view.setDate(1); let selected = null, editingId = null;
const $ = id => document.getElementById(id);
const pad = n => String(n).padStart(2, '0');
const key = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`;
// Session type is typed in by the officer; colour is picked from keywords in what they typed.
const typeClass = t => { t = (t || '').toLowerCase();
  if (/drill|formation|march/.test(t)) return 'drill';
  if (/field|exercise|bivouac|camp/.test(t)) return 'field';
  if (/phys|pt\b|fitness|run|sport/.test(t)) return 'physical';
  if (/academic|seminar|lecture|class|leadership|exam/.test(t)) return 'academic';
  return 'admin'; };
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const prettyDate = s => new Date(s + 'T00:00').toLocaleDateString('en-US', {month:'long', day:'numeric', year:'numeric'});

function render() {
  $('monthLabel').textContent = view.toLocaleDateString('en-US', {month:'long', year:'numeric'});
  const y = view.getFullYear(), m = view.getMonth(), first = new Date(y, m, 1).getDay(), days = new Date(y, m + 1, 0).getDate();
  let html = ['SUN','MON','TUE','WED','THU','FRI','SAT'].map(d => `<div class="dow">${d}</div>`).join('');
  for (let i = 0; i < first; i++) html += '<div class="day empty"></div>';
  for (let d = 1; d <= days; d++) {
    const k = key(y, m, d), ev = sessions.filter(s => s.date === k);
    html += `<div class="day ${selected === k ? 'sel' : ''}" data-d="${k}">${d}${ev.map(e => `<span class="ev t-${typeClass(e.type)}">${esc(e.title)}</span>`).join('')}</div>`;
  }
  $('cal').innerHTML = html;
  document.querySelectorAll('.day[data-d]').forEach(el => el.onclick = () => { selected = el.dataset.d; render(); showDetail(); });
  const up = [...sessions].sort((a, b) => a.date.localeCompare(b.date));
  $('upcoming').innerHTML = up.map(s => `<div class="upc"><div class="d">${+s.date.slice(8)}</div><div><strong>${s.title}</strong><br><small style="color:var(--muted)">${s.location} &middot; ${s.audience}</small></div></div>`).join('') || '<p style="color:var(--muted)">No sessions scheduled.</p>';
}

function showDetail() {
  const ev = sessions.filter(s => s.date === selected);
  let html = `<h3>${prettyDate(selected)}</h3>`;
  if (!ev.length) html += '<p style="color:var(--muted)">No session scheduled.</p>';
  ev.forEach(s => {
    html += `<div class="kv"><strong style="font-size:16px">${s.title}</strong></div>
      <div class="kv"><span class="label">Time</span>${s.time}</div>
      <div class="kv"><span class="label">Location</span>${s.location}</div>
      <div class="kv"><span class="label">Audience</span>${s.audience}</div>`;
    if (EDITABLE) html += `<div class="dlg-actions" style="justify-content:flex-start"><button class="btn" onclick="editSession(${s.id})">Edit</button><button class="btn danger" onclick="removeSession(${s.id})">Remove</button></div>`;
  });
  $('detail').innerHTML = html;
}

$('prev').onclick = () => { view.setMonth(view.getMonth() - 1); render(); };
$('next').onclick = () => { view.setMonth(view.getMonth() + 1); render(); };

if (EDITABLE) {
  window.openAddSession = () => { editingId = null; $('sessTitle').textContent = 'Add Training Session'; $('sessForm').reset(); if (selected) $('s_date').value = selected; sessDlg.showModal(); };
  window.editSession = id => { const s = sessions.find(x => x.id === id); editingId = id; $('sessTitle').textContent = 'Edit Training Session';
    $('s_title').value = s.title; $('s_date').value = s.date; $('s_time').value = s.time; $('s_loc').value = s.location; $('s_type').value = s.type; $('s_aud').value = s.audience; sessDlg.showModal(); };
  window.removeSession = id => { if (confirm('Remove this session?')) { sessions = sessions.filter(s => s.id !== id); render(); showDetail(); } };
  $('sessForm').addEventListener('submit', e => {
    if (e.submitter && e.submitter.value !== 'save') return;
    const data = {title:$('s_title').value, date:$('s_date').value, time:$('s_time').value, location:$('s_loc').value, type:$('s_type').value.trim(), audience:$('s_aud').value};
    if (editingId) Object.assign(sessions.find(s => s.id === editingId), data);
    else sessions.push({id: Date.now(), ...data});
    selected = data.date; view = new Date(data.date + 'T00:00'); view.setDate(1); render(); showDetail();
  });
}
render();
</script>
@endpush
