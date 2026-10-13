@props([
    'title' => 'Importvorschau', 'columns' => [], 'rows' => [],
    'emptyLabel' => 'Keine Importdaten zur Vorschau.',
    'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $states = ['valid' => 'Bereit', 'warning' => 'Prüfen', 'error' => 'Fehler', 'skipped' => 'Übersprungen', 'unknown' => 'Ungeprüft'];
    $safeRows = collect($rows)->map(function ($row) use ($states) {
        $state = $row['state'] ?? 'unknown';
        $row['_state'] = array_key_exists($state, $states) ? $state : 'unknown';
        $row['_label'] = $states[$row['_state']];
        return $row;
    })->all();
@endphp

<section {{ $attributes->class('bc-import-preview bc-import-preview--'.$density) }} aria-label="{{ $title }}">
    <header class="bc-import-preview__header">
        <strong>{{ $title }}</strong><span>{{ count($safeRows) }} {{ count($safeRows) === 1 ? 'Zeile' : 'Zeilen' }}</span>
    </header>
    @if (count($safeRows) && count($columns))
        <div class="bc-import-preview__scroll" role="region" aria-label="{{ $title }} – Tabelle, horizontal scrollbar" tabindex="0">
            <table>
                <thead><tr>
                    <th scope="col">Zeile</th>
                    @foreach ($columns as $column)
                        <th scope="col">{{ $column['label'] ?? 'Spalte' }}</th>
                    @endforeach
                    <th scope="col">Prüfstand</th>
                    <th scope="col">Hinweis</th>
                </tr></thead>
                <tbody>
                    @foreach ($safeRows as $index => $row)
                        <tr class="bc-import-preview__row bc-import-preview__row--{{ $row['_state'] }}">
                            <th scope="row">{{ $row['number'] ?? $index + 1 }}</th>
                            @foreach ($columns as $column)
                                <td>{{ $row['values'][$column['key'] ?? ''] ?? '—' }}</td>
                            @endforeach
                            <td><span class="bc-import-preview__state bc-import-preview__state--{{ $row['_state'] }}">{{ $row['_label'] }}</span></td>
                            <td>{{ $row['message'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="bc-import-preview__empty">{{ $emptyLabel }}</p>
    @endif
    @isset($actions) <div class="bc-import-preview__actions">{{ $actions }}</div> @endisset
</section>
