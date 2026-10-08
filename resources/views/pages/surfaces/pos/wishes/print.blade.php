@php
    $letterheadFile = ['farbe' => 'briefpapier-quer-farbe.png', 'sw' => 'briefpapier-quer-sw.png'][$letterhead] ?? null;
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Buchwünsche {{ now()->format('Y-m-d') }}</title>
    <style>
        /* Querformat mit Briefpapier (297 × 210 mm). Freie Fläche: unter dem Kopf (ca. 5 cm), vor dem Punktband rechts und über dem Fuß. */
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 9.5pt; color: #111; background: #fff; }
        .bar { padding: 8px 12px; background: #eee; font-size: 14px; }
        .bar button { padding: 4px 12px; margin-right: 12px; }
        .bar a { margin-right: 12px; }
        .paper { position: fixed; z-index: -1; top: 0; left: 0; width: 297mm; height: 210mm; display: none; }
        .page { padding: 5cm 2.2cm 3cm 2cm; -webkit-box-decoration-break: clone; box-decoration-break: clone; }
        h1 { margin: 0 0 1mm; font-size: 14pt; color: #58275a; }
        .meta { margin: 0 0 4mm; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1.4mm 2mm; border-bottom: .2mm solid #bbb; text-align: left; vertical-align: top; }
        th { font-size: 8.5pt; text-transform: uppercase; letter-spacing: .02em; border-bottom: .4mm solid #58275a; }
        tr { break-inside: avoid; }
        thead { display: table-header-group; }
        .num { width: 8mm; color: #555; }
        .nowrap, .isbn { white-space: nowrap; }
        .isbn { font-variant-numeric: tabular-nums; }
        .note { color: #444; font-size: 8.5pt; }
        @media print { .bar { display: none; } @if ($letterheadFile) .paper { display: block; } @endif }
        @media screen { body { background: #ddd; } .page { max-width: 297mm; margin: 12px auto; background: #fff; padding: 12mm; } }
    </style>
</head>
<body>
@if ($letterheadFile)
    <img class="paper" src="/brand/vdbs/{{ $letterheadFile }}" alt="" aria-hidden="true">
@endif
<div class="bar">
    <button type="button" data-print>Drucken / als PDF speichern</button>
    Im Druckdialog „Als PDF speichern“, Querformat und Ränder „Keine“ wählen.
    <a href="{{ route('pos.wishes.index', array_filter(['status' => request('status'), 'q' => request('q')])) }}">Zurück zur Übersicht</a>
    Briefpapier:
    <a href="{{ request()->fullUrlWithQuery(['briefpapier' => 'farbe']) }}">Farbe</a>
    <a href="{{ request()->fullUrlWithQuery(['briefpapier' => 'sw']) }}">Schwarz-Weiß</a>
    <a href="{{ request()->fullUrlWithQuery(['briefpapier' => 'ohne']) }}">Ohne</a>
</div>
<main class="page">
    <h1>Buchwünsche: {{ $filterLabel }}</h1>
    <p class="meta">
        {{ $wishes->count() }} {{ $wishes->count() === 1 ? 'Wunsch' : 'Wünsche' }}@if ($term !== '') zu „{{ $term }}“@endif, insgesamt erfasst: {{ $total }} · Stand {{ now()->format('d.m.Y') }}
    </p>
    <table>
        <thead>
            <tr>
                <th class="num">Nr.</th>
                <th>Titel</th>
                <th>Autor:in</th>
                <th>ISBN</th>
                <th>Von</th>
                <th>Datum</th>
                <th>Stand</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($wishes as $wish)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td>
                        {{ $wish->title }}
                        @if ($wish->note)<div class="note">Anmerkung: {{ $wish->note }}</div>@endif
                    </td>
                    <td>{{ $wish->author ?: '–' }}</td>
                    <td class="isbn">{{ $wish->isbn ?: '–' }}</td>
                    <td class="nowrap">{{ $wish->patron ? $wish->patron->last_name.', '.$wish->patron->first_name.($wish->patron->schoolClass ? ' ('.$wish->patron->schoolClass->name.')' : '') : ($wish->contact_name ?: 'ohne Person') }}</td>
                    <td class="nowrap">{{ $wish->created_at?->format('d.m.Y') }}</td>
                    <td class="nowrap">{{ $wish->status->label() }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Keine Wünsche gefunden.</td></tr>
            @endforelse
        </tbody>
    </table>
</main>
@vite(['resources/js/app.js'])
</body>
</html>
