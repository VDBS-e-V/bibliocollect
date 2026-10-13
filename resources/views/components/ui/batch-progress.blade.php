@props([
    'title' => 'Stapelverarbeitung', 'items' => [],
    'value' => null, 'max' => null,
    'density' => 'comfortable',
    'emptyLabel' => 'Keine Verarbeitungsschritte vorhanden.',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $states = ['done' => 'Abgeschlossen', 'running' => 'In Bearbeitung', 'failed' => 'Fehlgeschlagen', 'pending' => 'Ausstehend', 'skipped' => 'Übersprungen'];
    $rows = collect($items)->map(function ($item) use ($states) {
        $state = $item['state'] ?? 'pending';
        $item['_state'] = array_key_exists($state, $states) ? $state : 'pending';
        $item['_label'] = $states[$item['_state']];
        return $item;
    })->all();
    $doneCount = collect($rows)->where('_state', 'done')->count();
@endphp

<section {{ $attributes->class('bc-batch-progress bc-batch-progress--'.$density) }} aria-label="{{ $title }}">
    <header class="bc-batch-progress__header">
        <strong>{{ $title }}</strong>
        @if (count($rows)) <span>{{ $doneCount }} von {{ count($rows) }} Schritten abgeschlossen</span> @endif
    </header>
    @if ($value !== null && $max !== null)
        <x-ui.progress :label="$title" :value="$value" :max="$max" :compact="$density === 'compact'" description="Vom aufrufenden System übergebener Fortschrittswert." />
    @endif
    @if (count($rows))
        <ol class="bc-batch-progress__list">
            @foreach ($rows as $row)
                <li class="bc-batch-progress__item bc-batch-progress__item--{{ $row['_state'] }}">
                    <div class="bc-batch-progress__main">
                        <strong>{{ $row['title'] ?? 'Verarbeitungsschritt' }}</strong>
                        @if (filled($row['description'] ?? null)) <p>{{ $row['description'] }}</p> @endif
                    </div>
                    <span class="bc-batch-progress__state bc-batch-progress__state--{{ $row['_state'] }}">{{ $row['_label'] }}</span>
                </li>
            @endforeach
        </ol>
    @else
        <p class="bc-batch-progress__empty">{{ $emptyLabel }}</p>
    @endif
    @isset($actions)<div class="bc-batch-progress__actions">{{ $actions }}</div>@endisset
</section>
