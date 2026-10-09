@php
    use App\Foundation\Support\Code128Svg;
    use App\Foundation\Support\QrSvg;

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

        .label { display: grid; grid-template-columns: minmax(0, 1fr) 40mm; gap: 3mm; padding: 1.8mm 4mm 1.6mm 4.5mm; overflow: hidden; background: #fff; }
        .label .main { display: grid; grid-template-rows: 1fr auto auto; align-content: start; min-width: 0; }
        .label .what { font: bold 15pt/1.12 Arial, Helvetica, sans-serif; color: #58275a; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; align-self: center; }
        .label .what.long { font-size: 12pt; }
        .label .where { margin-top: .6mm; font: 6.2pt/1.2 Arial, Helvetica, sans-serif; color: #222; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .label .topics { margin-top: .3mm; font: 5.8pt/1.2 Arial, Helvetica, sans-serif; color: #444; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .label .side { display: grid; grid-template-rows: 3.6mm 1fr auto; justify-items: end; min-width: 0; row-gap: .6mm; }
        .label .brand { display: flex; align-items: center; justify-content: flex-end; gap: 1mm; width: 100%; }
        .label .brand img { height: 3.6mm; width: auto; }
        .label .brand span { font: bold 6.5pt/1 Arial, Helvetica, sans-serif; color: #58275a; }
        .label .scan { width: 100%; display: flex; align-items: center; justify-content: flex-end; min-height: 0; }
        .label .scan svg.bar128 { width: 100%; max-width: 40mm; height: 11mm; display: block; }
        .label .scan.qr svg { width: 14mm; height: 14mm; display: block; }
        .label .scan.qr { flex: 0 0 auto; width: auto; align-self: end; margin-right: .5mm; padding: 1mm; background: #fff; }
        .label.qr .main { grid-template-rows: auto 1fr auto auto; }
        .label.qr .side { grid-template-rows: 1fr auto; row-gap: 1.4mm; }
        .label.qr .brand-left { display: flex; align-items: center; gap: 1mm; margin-bottom: .8mm; }
        .label.qr .brand-left img { height: 3.2mm; width: auto; }
        .label.qr .brand-left span { font: bold 6pt/1 Arial, Helvetica, sans-serif; color: #58275a; }
        .label .nobarcode { font: 6pt/1.2 Arial, sans-serif; color: #444; text-align: right; }
        .label .loc { font: bold 11pt/1 Arial, Helvetica, sans-serif; color: #000; border: .35mm solid #000; border-radius: 1mm; padding: .7mm 1.8mm .6mm; white-space: nowrap; max-width: 100%; overflow: hidden; text-overflow: ellipsis; }
        .label .loc.big { font-size: 17pt; padding: 1.4mm 2.6mm; align-self: center; }
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
                <div class="label{{ $codeType === 'qr' ? ' qr' : '' }}">
                    <div class="main">
                        @if ($codeType === 'qr')<div class="brand-left"><img src="/brand/vdbs/mark.svg" alt=""><span>BiblioCollect</span></div>@endif
                        <div class="what {{ mb_strlen($label['headline']) > 38 ? 'long' : '' }}">{{ $label['headline'] }}</div>
                        <div class="where">{{ $label['where'] }}</div>
                        @if ($withTopics && $label['topics'] !== '' && $label['label'] !== '')
                            <div class="topics">Themen: {{ $label['topics'] }}</div>
                        @endif
                    </div>
                    <div class="side">
                        @if ($codeType !== 'qr')<div class="brand"><img src="/brand/vdbs/mark.svg" alt=""><span>BiblioCollect</span></div>@endif
                        @if ($codeType === 'qr')
                            <div class="scan qr">{!! QrSvg::render($label['url'], 'QR-Code '.$label['code']) !!}</div>
                        @elseif ($codeType === 'strich' && $label['barcode'])
                            <div class="scan">{!! str_replace('<svg ', '<svg class="bar128" ', Code128Svg::render($label['code'])) !!}</div>
                        @elseif ($codeType === 'strich')
                            <div class="scan"><span class="nobarcode">Kein Strichcode (Sonderzeichen oder zu lang)</span></div>
                        @else
                            <div class="scan"></div>
                        @endif
                        <div class="loc {{ $codeType === 'keiner' ? 'big' : '' }}">{{ $label['code'] }}</div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endforeach
@vite('resources/js/app.js')
</body>
</html>
