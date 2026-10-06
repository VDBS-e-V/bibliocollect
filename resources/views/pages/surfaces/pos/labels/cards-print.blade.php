@php
    use App\Foundation\Support\Code128Svg;

    $sheets = $patrons->chunk($perSheet);
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Bibliotheksausweise</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #000; background: #fff; }
        .bar { padding: 8px 12px; background: #eee; font-size: 14px; }
        .bar button { padding: 4px 12px; margin-right: 12px; }
        .sheet { width: 210mm; height: 297mm; padding: 12mm 15mm 0; display: grid; grid-template-columns: repeat(2, 85.6mm); grid-auto-rows: 54mm; column-gap: 4mm; row-gap: 3mm; break-after: page; }
        .sheet:last-child { break-after: auto; }
        .card { border: .3mm solid #000; border-radius: 3mm; padding: 4mm 5mm; display: grid; grid-template-rows: auto 1fr auto auto; gap: 1mm; overflow: hidden; }
        .card .org { font: bold 8pt Arial, sans-serif; letter-spacing: .4px; text-transform: uppercase; border-bottom: .3mm solid #000; padding-bottom: 1.5mm; }
        .card .name { font: bold 15pt/1.15 Arial, sans-serif; align-self: center; }
        .card .meta { font: 9pt Arial, sans-serif; }
        .card svg { width: 100%; height: 11mm; display: block; }
        .card .code { font: 9pt 'Courier New', monospace; text-align: center; }
        @media print { .bar { display: none; } }
        @media screen { body { background: #ddd; } .sheet { background: #fff; margin: 12px auto; box-shadow: 0 0 6px rgba(0,0,0,.3); } }
    </style>
</head>
<body>
<div class="bar">
    <button type="button" data-print>Drucken</button>
    {{ $patrons->count() }} Ausweise · A4, tatsächliche Größe (100 %), keine Seitenanpassung. Auf festem Papier drucken und ausschneiden.
</div>
@foreach ($sheets as $sheet)
    <div class="sheet">
        @foreach ($sheet as $patron)
            <div class="card">
                <div class="org">{{ config('app.name', 'BiblioCollect') }} · Bibliotheksausweis</div>
                <div class="name">{{ $patron->first_name }} {{ $patron->last_name }}</div>
                <div class="meta">@if ($patron->schoolClass)Klasse {{ $patron->schoolClass->name }} · @endif{{ $patron->library_number }}</div>
                <div>
                    {!! Code128Svg::render($patron->library_number) !!}
                    <div class="code">{{ $patron->library_number }}</div>
                </div>
            </div>
        @endforeach
    </div>
@endforeach
@vite('resources/js/app.js')
</body>
</html>
