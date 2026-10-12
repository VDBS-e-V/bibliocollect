@props([
    'title' => 'Datensätze', 'columns' => [], 'rows' => [], 'selectable' => false,
    'selectName' => 'selected[]', 'emptyLabel' => 'Keine Datensätze vorhanden.',
    'density' => 'comfortable',
])
@php $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable'; @endphp
<section {{ $attributes->class('bc-data-grid bc-data-grid--'.$density) }} data-ui-data-grid aria-label="{{ $title }}">
    <div class="bc-data-grid__header">
        <strong>{{ $title }}</strong><span>{{ count($rows) }} {{ count($rows) === 1 ? 'Eintrag' : 'Einträge' }}</span>
    </div>
    @if (count($columns) && count($rows))
        <x-ui.table class="bc-data-grid__scroll" role="region" tabindex="0" aria-label="{{ $title }} – horizontal scrollbar">
            <thead><tr>
                @if ($selectable)
                    <th scope="col" class="bc-data-grid__select-cell">
                        <input type="checkbox" data-ui-grid-select-all aria-label="Alle Einträge dieser Tabelle auswählen">
                    </th>
                @endif
                @foreach ($columns as $column)
                    <x-ui.sort-header :label="$column['label'] ?? 'Spalte'" :href="$column['sortHref'] ?? null" :direction="$column['direction'] ?? null" />
                @endforeach
            </tr></thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @if ($selectable)
                            <td class="bc-data-grid__select-cell">
                                <input type="checkbox" data-ui-grid-item name="{{ $selectName }}" value="{{ $row['id'] ?? '' }}"
                                    aria-label="Auswählen: {{ $row['label'] ?? ($row['id'] ?? 'Eintrag') }}"
                                    @disabled(! empty($row['disabled']))>
                            </td>
                        @endif
                        @foreach ($columns as $column)
                            <td>{{ $row['cells'][$column['key'] ?? ''] ?? '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
        @if ($selectable)
            <p class="bc-data-grid__count" data-ui-grid-count role="status">0 ausgewählt</p>
        @endif
        @isset($actions)<div class="bc-data-grid__actions">{{ $actions }}</div>@endisset
    @else
        <p class="bc-data-grid__empty">{{ $emptyLabel }}</p>
    @endif
</section>
