@php
    use App\Foundation\Support\Code128Svg;

    $cells = array_merge(array_fill(0, $skip, null), $labels);
    $sheets = array_chunk($cells, $perSheet);
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Etiketten für Regalbretter</title>
    <style>
        /* Etikettenbogen 105 × 26 mm, 2 × 11 = 22 Stück, randlos nach links und rechts, oben und unten gleich viel Rand (297 − 286 = 11 mm). */
        :root { --label-x: 0mm; --label-y: 0mm; }
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #000; background: #fff; }
        .bar { padding: 8px 12px; background: #eee; font-size: 14px; }
        .bar button { padding: 4px 12px; margin-right: 12px; }
        .bar label { margin-left: 12px; }
        .bar input[type=number] { width: 4.5em; }
        .sheet { width: 210mm; height: 297mm; padding: calc(5.5mm + var(--label-y)) 0 0 var(--label-x); display: grid; grid-template-columns: repeat(2, 105mm); grid-auto-rows: 26mm; break-after: page; }
        .sheet:last-child { break-after: auto; }

        .label { display: grid; grid-template-columns: minmax(0, 1fr) 46mm; gap: 2.5mm; padding: 2mm 4mm 1.8mm 4.5mm; overflow: hidden; background: #fff; }
        .label .main { display: grid; grid-template-rows: auto auto 1fr; align-content: start; min-width: 0; }
        .label .loc { font: bold 24pt/1 Arial, Helvetica, sans-serif; color: #000; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .label .what { margin-top: 1.2mm; font: bold 9pt/1.1 Arial, Helvetica, sans-serif; color: #58275a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .label .where { margin-top: .8mm; font: 6.5pt/1.2 Arial, Helvetica, sans-serif; color: #222; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .label .topics { margin-top: .5mm; font: 5.8pt/1.2 Arial, Helvetica, sans-serif; color: #444; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .label .side { display: grid; grid-template-rows: 5mm 1fr auto; justify-items: center; min-width: 0; }
        .label .brand { display: flex; align-items: center; justify-content: flex-end; gap: 1.2mm; width: 100%; }
        .label .brand img { height: 5mm; width: auto; }
        .label .brand span { font: bold 8pt/1 Arial, Helvetica, sans-serif; color: #58275a; }
        .label svg { width: 100%; max-width: 46mm; height: 11mm; align-self: center; display: block; }
        .label .nobarcode { align-self: center; font: 6pt/1.2 Arial, sans-serif; color: #444; text-align: center; }
        .label .code { font: bold 7.5pt/1 'Courier New', monospace; text-align: center; letter-spacing: .6px; white-space: nowrap; overflow: hidden; max-width: 100%; }
        .empty { visibility: hidden; }
        @media print { .bar { display: none; } .sheet { margin: 0; } .label * { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
        @media screen { body { background: #ddd; } .sheet { background: #fff; margin: 12px auto; box-shadow: 0 0 6px rgba(0,0,0,.3); } .label { outline: 1px dashed #ccc; } }
    </style>
</head>
<body>
<div class="bar">
    <button type="button" data-print>Drucken</button>
    {{ count($labels) }} Etiketten · A4, Etikettenbogen {{ $perSheet }} Stück (105 × 26 mm), tatsächliche Größe (100 %), keine Seitenanpassung.
    <label>Feinabgleich rechts <input type="number" step="0.1" value="0" data-offset="--label-x"> mm</label>
    <label>nach unten <input type="number" step="0.1" value="0" data-offset="--label-y"> mm</label>
</div>
@foreach ($sheets as $sheet)
    <div class="sheet">
        @foreach ($sheet as $label)
            @if ($label === null)
                <div class="label empty"></div>
            @else
                <div class="label">
                    <div class="main">
                        <div class="loc">{{ $label['code'] }}</div>
                        <div class="what">{{ $label['label'] }}</div>
                        <div class="where">{{ $label['where'] }}@if ($withTopics && $label['topics'] !== '')<br>Themen: {{ $label['topics'] }}@endif</div>
                    </div>
                    <div class="side">
                        <div class="brand"><img src="/brand/vdbs/mark.svg" alt=""><span>BiblioCollect</span></div>
                        @if ($label['barcode'])
                            {!! Code128Svg::render($label['code']) !!}
                        @else
                            <div class="nobarcode">Kein Strichcode (Sonderzeichen oder zu lang)</div>
                        @endif
                        <div class="code">{{ $label['code'] }}</div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endforeach
@vite('resources/js/app.js')
</body>
</html>
