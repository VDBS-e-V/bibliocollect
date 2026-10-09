@php
    $files = ['farbe' => 'briefpapier', 'sw' => 'briefpapier-sw'];
    $key = $letterhead;
    $letterheadFile = isset($files[$key]) ? ($landscape ? ($key === 'farbe' ? 'briefpapier-quer-farbe.png' : 'briefpapier-quer-sw.png') : ($key === 'farbe' ? 'briefpapier-farbe.png' : 'briefpapier-sw.png')) : null;
    $heading = $mode === 'ueberfaellig' ? 'Überfällige Ausleihen' : 'Offene Ausleihen';
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Klassenlisten {{ now()->format('Y-m-d') }}</title>
    <style>
        /* Je Klasse eine Seite (bei vielen Zeilen mehrere) auf dem Briefpapier, Hoch- oder Querformat. */
        @page { size: A4 {{ $landscape ? 'landscape' : 'portrait' }}; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #111; background: #fff; }
        .bar { padding: 8px 12px; background: #eee; font-size: 14px; }
        .bar button { padding: 4px 12px; margin-right: 12px; }
        .bar a { margin-right: 12px; }
        .paper { position: fixed; z-index: -1; top: 0; left: 0; width: {{ $landscape ? '297mm' : '210mm' }}; height: {{ $landscape ? '210mm' : '297mm' }}; display: none; }
        .class { padding: {{ $landscape ? '5cm 2.2cm 3cm 2cm' : '5cm 2.4cm 3.2cm 2.6cm' }}; break-after: page; -webkit-box-decoration-break: clone; box-decoration-break: clone; }
        .class:last-of-type { break-after: auto; }
        h1 { margin: 0 0 1mm; font-size: 14pt; color: #58275a; }
        .meta { margin: 0 0 4mm; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1.4mm 2mm; border-bottom: .2mm solid #bbb; text-align: left; vertical-align: top; }
        th { font-size: 8.5pt; text-transform: uppercase; letter-spacing: .02em; border-bottom: .4mm solid #58275a; }
        tr { break-inside: avoid; }
        thead { display: table-header-group; }
        .num { white-space: nowrap; font-variant-numeric: tabular-nums; }
        .note { margin-top: 5mm; color: #333; font-size: 9pt; }
        @media print { .bar { display: none; } @if ($letterheadFile) .paper { display: block; } @endif }
        @media screen { body { background: #ddd; } .class { max-width: {{ $landscape ? '297mm' : '210mm' }}; margin: 12px auto; background: #fff; padding: 12mm; } }
    </style>
</head>
<body>
@if ($letterheadFile)
    <img class="paper" src="/brand/vdbs/{{ $letterheadFile }}" alt="" aria-hidden="true">
@endif
<div class="bar">
    <button type="button" data-print>Drucken / als PDF speichern</button>
    Im Druckdialog „Als PDF speichern“, {{ $landscape ? 'Querformat' : 'Hochformat' }} und Ränder „Keine“ wählen.
    <a href="{{ route('pos.reports.class-loans', array_filter(['modus' => request('modus'), 'klasse' => request('klasse')])) }}">Zurück</a>
    Format:
    <a href="{{ request()->fullUrlWithQuery(['format' => 'hoch']) }}">Hochformat</a>
    <a href="{{ request()->fullUrlWithQuery(['format' => 'quer']) }}">Querformat</a>
    Briefpapier:
    <a href="{{ request()->fullUrlWithQuery(['briefpapier' => 'farbe']) }}">Farbe</a>
    <a href="{{ request()->fullUrlWithQuery(['briefpapier' => 'sw']) }}">Schwarz-Weiß</a>
    <a href="{{ request()->fullUrlWithQuery(['briefpapier' => 'ohne']) }}">Ohne</a>
</div>
@forelse ($groups as $group)
    <section class="class">
        <h1>{{ $group['label'] === 'Ohne Klasse' ? 'Ohne Klasse (Lehrkräfte und Mitarbeiter:innen)' : 'Klasse '.$group['label'] }}</h1>
        <p class="meta">
            @if ($group['homeroom'])Klassenleitung: <strong>{{ $group['homeroom'] }}</strong> · @endif
            {{ $heading }} · Stand {{ $today->format('d.m.Y') }} · {{ count($group['rows']) }} {{ count($group['rows']) === 1 ? 'Medium' : 'Medien' }}
        </p>
        <table>
            <thead>
                <tr><th>Name</th><th>Medium</th><th>Barcode</th><th>Fällig</th><th>Überfällig</th></tr>
            </thead>
            <tbody>
                @foreach ($group['rows'] as $row)
                    <tr>
                        <td><strong>{{ $row['patron'] }}</strong></td>
                        <td>{{ $row['title'] }}</td>
                        <td class="num">{{ $row['barcode'] }}</td>
                        <td class="num">{{ $row['due_on'] }}</td>
                        <td class="num">{{ $row['days_overdue'] > 0 ? $row['days_overdue'].' Tage' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="note">Bitte erinnern Sie die Schüler:innen an die Rückgabe in der Schulbibliothek.</p>
    </section>
@empty
    <section class="class"><h1>{{ $heading }}</h1><p class="meta">Es gibt nichts zu melden.</p></section>
@endforelse
@vite(['resources/js/app.js'])
</body>
</html>
