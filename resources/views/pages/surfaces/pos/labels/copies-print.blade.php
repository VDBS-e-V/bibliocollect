@php
    use App\Foundation\Support\Code128Svg;

    // Leere Plätze am Anfang, damit angebrochene Bögen weiterverwendet werden können.
    $cells = array_merge(array_fill(0, $skip, null), $copies->all());
    $sheets = array_chunk($cells, $perSheet);
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Exemplar-Etiketten</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #000; background: #fff; }
        .bar { padding: 8px 12px; background: #eee; font-size: 14px; }
        .bar button { padding: 4px 12px; margin-right: 12px; }
        .sheet { width: 210mm; height: 297mm; padding: 15.1mm 7.2mm 0; display: grid; grid-template-columns: repeat(3, 63.5mm); grid-auto-rows: 38.1mm; column-gap: 2.5mm; break-after: page; }
        .sheet:last-child { break-after: auto; }
        .label { padding: 2.5mm 3mm; display: grid; grid-template-rows: auto auto auto 1fr; gap: .6mm; overflow: hidden; }
        .label svg { width: 100%; height: 14mm; display: block; }
        .label .code { font: 10pt/1.1 'Courier New', monospace; text-align: center; letter-spacing: .5px; }
        .label .sig { font: bold 11pt/1.1 Arial, sans-serif; text-align: center; }
        .label .title { font: 7.5pt/1.15 Arial, sans-serif; text-align: center; overflow: hidden; }
        .empty { visibility: hidden; }
        @media print { .bar { display: none; } .sheet { margin: 0; } }
        @media screen { body { background: #ddd; } .sheet { background: #fff; margin: 12px auto; box-shadow: 0 0 6px rgba(0,0,0,.3); } .label { outline: 1px dashed #ccc; } }
    </style>
</head>
<body>
<div class="bar">
    <button type="button" data-print>Drucken</button>
    {{ $copies->count() }} Etiketten · A4, Etikettenbogen {{ $perSheet }} Stück, tatsächliche Größe (100 %), keine Seitenanpassung.
</div>
@foreach ($sheets as $sheet)
    <div class="sheet">
        @foreach ($sheet as $copy)
            @if ($copy === null)
                <div class="label empty"></div>
            @else
                <div class="label">
                    {!! Code128Svg::render($copy->barcode) !!}
                    <div class="code">{{ $copy->barcode }}</div>
                    <div class="sig">{{ $copy->signature?->signature ?: $copy->shelf_location }}</div>
                    <div class="title">{{ \Illuminate\Support\Str::limit($copy->edition->title->preferred_title, 60) }}</div>
                </div>
            @endif
        @endforeach
    </div>
@endforeach
@vite('resources/js/app.js')
</body>
</html>
