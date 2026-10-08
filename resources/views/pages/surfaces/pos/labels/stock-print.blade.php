@php
    use App\Foundation\Support\Code128Svg;

    // Leere Plätze am Anfang, damit angebrochene Bögen weiterverwendet werden können.
    $cells = array_merge(array_fill(0, $skip, null), $numbers);
    $sheets = array_chunk($cells, $perSheet);
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Etiketten auf Vorrat</title>
    @include('pages.surfaces.pos.labels._sheet-style')
</head>
<body>
<div class="bar">
    <button type="button" data-print>Drucken</button>
    {{ count($numbers) }} Etiketten, {{ $numbers[0] }} bis {{ $numbers[count($numbers) - 1] }} · A4, Etikettenbogen {{ $perSheet }} Stück (70 × 36 mm), tatsächliche Größe (100 %), keine Seitenanpassung.
    <label>Feinabgleich rechts <input type="number" step="0.1" value="0" data-offset="--label-x"> mm</label>
    <label>nach unten <input type="number" step="0.1" value="0" data-offset="--label-y"> mm</label>
</div>
@foreach ($sheets as $sheet)
    <div class="sheet">
        @foreach ($sheet as $number)
            @if ($number === null)
                <div class="label empty"></div>
            @else
                <div class="label">
                    <div class="head">
                        <img class="logo" src="/brand/vdbs/mark.svg" alt="">
                        <span class="name">BiblioCollect</span>
                    </div>
                    <div class="mid"></div>
                    {!! Code128Svg::render($number) !!}
                    <div class="code">{{ $number }}</div>
                </div>
            @endif
        @endforeach
    </div>
@endforeach
@vite('resources/js/app.js')
</body>
</html>
