@props([
    'items' => [],
    'title' => 'Vergleich',
    'max' => null,
])

@php
    $peak = max(1, (float) ($max ?? collect($items)->max('value') ?? 1));
@endphp

<figure {{ $attributes->class('bc-metric-bars') }}>
    <figcaption class="bc-metric-bars__title">{{ $title }}</figcaption>
    <div class="bc-metric-bars__plot" aria-hidden="true">
        @foreach ($items as $item)
            @php
                $number = max(0, (float) ($item['value'] ?? 0));
                $percentage = round(min(100, $number / $peak * 100), 1);
                $tone = in_array($item['tone'] ?? 'green', ['green', 'purple', 'orange', 'blue'], true) ? $item['tone'] : 'green';
            @endphp
            <div class="bc-metric-bars__line">
                <span>{{ $item['label'] ?? 'Wert' }}</span>
                <span class="bc-metric-bars__track">
                    <span class="bc-metric-bars__fill bc-metric-bars__fill--{{ $tone }}" style="width:{{ $percentage }}%"></span>
                </span>
                <strong>{{ $item['value'] ?? 0 }}</strong>
            </div>
        @endforeach
    </div>
    <table class="bc-metric-bars__data">
        <caption>{{ $title }} – zugängliche Datentabelle</caption>
        <thead><tr><th scope="col">Kategorie</th><th scope="col">Wert</th></tr></thead>
        <tbody>
            @foreach ($items as $item)
                <tr><th scope="row">{{ $item['label'] ?? 'Wert' }}</th><td>{{ $item['value'] ?? 0 }}</td></tr>
            @endforeach
        </tbody>
    </table>
</figure>
