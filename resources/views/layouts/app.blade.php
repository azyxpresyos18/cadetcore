@php
  $role = $role ?? trim($__env->yieldContent('role', 'adjutant'));
  $labels = ['adjutant'=>'Corps Adjutant','corps_s1'=>'Corps S1 Personnel Officer','battalion_s1'=>'Battalion S1 Officer','platoon_leader'=>'Platoon Leader','cadet'=>'Cadet'];
  $prefixes = ['adjutant'=>'adjutant','corps_s1'=>'corps','battalion_s1'=>'battalion','platoon_leader'=>'platoon','cadet'=>'cadet'];
  $pages = ['dashboard'=>'Dashboard','schedule'=>'Training Schedule','merits'=>'Merit / Demerit','cadets'=>'Cadet Records','officers'=>'Officers','attendance'=>'Attendance','reports'=>'Reports','yearly'=>'Yearly Update'];
  $staff = ['dashboard','schedule','merits','cadets','attendance','reports'];
  $corps = ['dashboard','schedule','merits','cadets','officers','attendance','reports'];
  $sets = ['adjutant'=>array_merge($corps, ['yearly']),'corps_s1'=>$corps,'battalion_s1'=>$staff,'platoon_leader'=>['dashboard','schedule','attendance','merits'],'cadet'=>['attendance','schedule','merits']];
  $roleLabel = $labels[$role]; $name = session('display_name') ?? $labels[$role]; $prefix = $prefixes[$role];
  $nav = [];
  foreach ($sets[$role] as $pg) { $nav[] = [$prefix.'.'.$pg, ($role === 'cadet' && $pg === 'merits') ? 'Merit & Demerit' : $pages[$pg]]; }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>CadetCore - @yield('title')</title>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Outfit:wght@400;600&family=Space+Mono&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/cadetcore.css') }}">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand">
      <img src="{{ asset('images/logo.png') }}" alt="" onerror="this.style.display='none'">
      <span class="brand-name">CADETCORE</span>
    </div>
    <div class="who"><small>Logged in as</small><strong>{{ $name }}</strong><small>{{ $roleLabel }}</small></div>
    <nav class="nav">
      @foreach ($nav as [$route, $label])
        <a href="{{ route($route) }}" class="{{ request()->routeIs($route) || ($route === 'adjutant.yearly' && request()->routeIs('adjutant.years')) ? 'active' : '' }}">{{ $label }}</a>
      @endforeach
    </nav>
    <form class="signout" method="POST" action="{{ route('logout') }}">@csrf<button type="submit">&#x21AA; Sign Out</button></form>
  </aside>
  <main class="main">
    <div class="topbar">
      <div><h1>@yield('heading')</h1><p>@yield('subtitle')</p></div>
      <div>@yield('actions')</div>
    </div>
    @yield('content')
  </main>
</div>
<script>
// Small helper used by every page that talks to the backend. Throws an Error with the server's message on failure.
window.api = async (method, url, body) => {
  const res = await fetch(url, {
    method,
    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  let data = {};
  try { data = await res.json(); } catch (e) {}
  if (!res.ok) {
    const first = data.errors ? Object.values(data.errors).flat()[0] : null;
    throw new Error(first || data.message || 'Something went wrong. Please try again.');
  }
  return data;
};
</script>
@stack('scripts')
</body>
</html>
