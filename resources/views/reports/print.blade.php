<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>CadetCore - {{ $title }}</title>
  <style>
    body { font-family: Arial, Helvetica, sans-serif; color: #1c2410; margin: 32px; }
    h1 { margin: 0 0 4px; font-size: 22px; }
    p.sub { margin: 0 0 18px; color: #555; font-size: 13px; }
    table { border-collapse: collapse; width: 100%; font-size: 12px; }
    th, td { border: 1px solid #bbb; padding: 5px 8px; text-align: left; }
    th { background: #e9ecd9; }
    tr:nth-child(even) td { background: #f7f8f1; }
    .bar { margin-bottom: 16px; }
    button { padding: 8px 16px; font-size: 14px; cursor: pointer; }
    @media print { .bar { display: none; } body { margin: 12px; } }
  </style>
</head>
<body>
  <div class="bar"><button onclick="window.print()">Save as PDF / Print</button></div>
  <h1>CadetCore &mdash; {{ $title }}</h1>
  <p class="sub">{{ $subtitle }} &middot; Generated {{ now()->format('d M Y H:i') }}</p>
  <table>
    <thead><tr>@foreach ($headers as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
    <tbody>
    @forelse ($rows as $row)
      <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
    @empty
      <tr><td colspan="{{ count($headers) }}">No records match these filters.</td></tr>
    @endforelse
    </tbody>
  </table>
  <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
</body>
</html>
