@props(['title' => 'Berechtigungsübersicht', 'columns' => [], 'rows' => [], 'density' => 'comfortable', 'emptyLabel' => 'Keine Berechtigungsdaten vorhanden.'])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $labels = ['allowed' => 'Erlaubt', 'denied' => 'Nicht erlaubt', 'conditional' => 'Eingeschränkt', 'unknown' => 'Unbekannt'];
@endphp
<section {{ $attributes->class('bc-permission-matrix bc-permission-matrix--'.$density) }} aria-label="{{ $title }}">
    <strong class="bc-permission-matrix__title">{{ $title }}</strong>
    @if (count($columns) && count($rows))
        <div class="bc-permission-matrix__scroll" tabindex="0" role="region" aria-label="{{ $title }} – Tabelle, horizontal scrollbar">
            <table>
                <thead><tr><th scope="col">Bereich</th>@foreach ($columns as $column)<th scope="col">{{ $column['label'] ?? 'Rolle' }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr><th scope="row">{{ $row['label'] ?? 'Funktion' }}</th>
                            @foreach ($columns as $column)
                                @php $state = $row['values'][$column['key'] ?? ''] ?? 'unknown'; $state = isset($labels[$state]) ? $state : 'unknown'; @endphp
                                <td><span class="bc-permission-matrix__status bc-permission-matrix__status--{{ $state }}">{{ $labels[$state] }}</span></td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="bc-permission-matrix__empty">{{ $emptyLabel }}</p>
    @endif
</section>
