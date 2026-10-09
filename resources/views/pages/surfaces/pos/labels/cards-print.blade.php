@php
    use App\Foundation\Support\Code128Svg;

    $modeLabel = ['beide' => 'beidseitig', 'vorder' => 'nur Vorderseiten', 'rueck' => 'nur Rückseiten'][$mode];
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Bibliotheksausweise Charge {{ $batch }}, {{ $modeLabel }}</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        :root { --dx: 0mm; --dy: 0mm; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #000; background: #fff; }
        .bar { padding: 8px 12px; background: #eee; font-size: 14px; }
        .bar button { padding: 4px 12px; margin-right: 12px; }
        .bar label { margin-left: 12px; }
        .bar input { width: 4.5em; }
        /* Avery Zweckform C32016: 85 × 54 mm, 2 × 5 Karten, randlos aneinander, Rand links/rechts 20 mm, oben/unten 13,5 mm. */
        .sheet { position: relative; width: 210mm; height: 297mm; padding: 13.5mm 20mm 0; display: grid; grid-template-columns: repeat(2, 85mm); grid-auto-rows: 54mm; break-after: page; overflow: hidden; }
        .sheet > .card { transform: translate(var(--dx), var(--dy)); }
        .sheet:last-child { break-after: auto; }
        .card { position: relative; overflow: hidden; background-color: #fff; background-size: 100% 100%; background-repeat: no-repeat; }
        .card.empty { visibility: hidden; }
        /* Weiße Fläche mit 6 mm Rand rund um das Motiv. */
        .card .panel { position: absolute; inset: 6mm; background: #fff; border-radius: 3mm; padding: 3mm 3.9mm 2.6mm; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; }
        .card .namebox { height: 12.7mm; background: #f4f6f5; border: .5mm solid #d8dcda; border-radius: 1.7mm; padding: 1.8mm 2.7mm; }
        .card .namebox span { font: bold 7pt Arial, sans-serif; letter-spacing: .03em; text-transform: uppercase; color: #4a5a55; }
        .card .brand { display: flex; align-items: center; justify-content: space-between; gap: 3mm; }
        .card .brand img { height: 6.5mm; width: auto; display: block; }
        .card .brand span { font: bold 7pt Arial, sans-serif; letter-spacing: .4px; text-transform: uppercase; text-align: right; }
        .card svg { width: 100%; height: 8.5mm; display: block; }
        .card .code { font: 8pt 'Courier New', monospace; text-align: center; line-height: 1.1; }
        /* Fertige Vorlage (Motiv mit weißer Fläche und Namensfeld): nur Logo, Strichcode und Nummer kommen darauf. */
        .card .content { position: absolute; left: 9.9mm; right: 9.9mm; top: 24mm; bottom: 8.6mm; display: flex; flex-direction: column; justify-content: space-between; }
        .card.back { display: flex; align-items: center; justify-content: center; }
        .card.back .badge { background: #fff; border-radius: 3mm; padding: 3mm 5mm; width: 62mm; }
        .card.back .badge img { width: 100%; height: auto; display: block; }
        @media print { .bar { display: none; } }
        @media screen { body { background: #ddd; } .sheet { background: #fff; margin: 12px auto; box-shadow: 0 0 6px rgba(0,0,0,.3); } .sheet > .card { outline: 1px dotted #aaa; outline-offset: -1px; } }
    </style>
</head>
<body>
<div class="bar">
    <button type="button" data-print>Drucken</button>
    Charge {{ $batch }} · {{ $modeLabel }} · {{ $count }} Ausweise · Avery Zweckform C32016 (85 × 54 mm, 10 Karten je Bogen), randlos.
    Im Druckdialog: A4, Maßstab 100 % („Tatsächliche Größe“), Ränder „Keine“, keine Kopf- und Fußzeilen, Hintergrundgrafiken an.
    @if ($mode === 'beide')
        Beidseitig: Im Druckdialog „Beidseitig“ / „Duplex“ mit „Wenden an der langen Kante“ wählen. Die Seiten folgen als Vorderseite, Rückseite, Vorderseite, Rückseite …
    @elseif ($mode === 'rueck')
        Zum Beidseitig-Druck von Hand: den Bogen mit der bedruckten Vorderseite wieder einlegen (Wenden an der langen Kante); die Rückseiten sind dafür gespiegelt angeordnet.
    @endif
    <label>Versatz rechts (mm) <input type="number" step="0.1" value="0" data-offset="--dx"></label>
    <label>Versatz unten (mm) <input type="number" step="0.1" value="0" data-offset="--dy"></label>
</div>
@foreach ($pages as $page)
    @php
        $front = $page['side'] === 'vorder';
    @endphp
    <div class="sheet">
        @foreach ($page['slots'] as $slot)
            @if ($slot === null)
                <div class="card empty"></div>
            @else
                @php
                    $url = $slot['motif'] ? ($front ? $slot['motif']->frontUrl() : $slot['motif']->backUrl()) : null;
                    $style = $url ? "background-image: url('".$url."')" : '';
                @endphp
                @if ($front && $url)
                    <div class="card" style="{{ $style }}">
                        <div class="content">
                            <div class="brand">
                                <img src="/brand/vdbs/logo-light.svg" alt="VDBS">
                                <span>Bibliotheks&shy;ausweis</span>
                            </div>
                            <div>
                                {!! Code128Svg::render($slot['card']->number) !!}
                                <div class="code">{{ $slot['card']->number }}</div>
                            </div>
                        </div>
                    </div>
                @elseif ($front)
                    <div class="card" style="{{ $style }}">
                        <div class="panel">
                            <div class="namebox"><span>Name</span></div>
                            <div class="brand">
                                <img src="/brand/vdbs/logo-light.svg" alt="VDBS">
                                <span>Bibliotheks&shy;ausweis</span>
                            </div>
                            <div>
                                {!! Code128Svg::render($slot['card']->number) !!}
                                <div class="code">{{ $slot['card']->number }}</div>
                            </div>
                        </div>
                    </div>
                @elseif ($url)
                    <div class="card back" style="{{ $style }}"></div>
                @else
                    <div class="card back"><div class="badge"><img src="/brand/vdbs/logo-light.svg" alt="VDBS e.V."></div></div>
                @endif
            @endif
        @endforeach
    </div>
@endforeach
@vite('resources/js/app.js')
</body>
</html>
