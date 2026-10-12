@props([
    'items' => [],
    'label' => 'Wochenübersicht',
    'compact' => false,
])

<section {{ $attributes->class([
    'bc-week-schedule',
    'bc-week-schedule--compact' => $compact,
]) }} aria-label="{{ $label }}">
    <dl class="bc-week-schedule__list">
        @foreach ($items as $item)
            <div @class([
                'bc-week-schedule__day',
                'bc-week-schedule__day--closed' => ! empty($item['closed']),
            ])>
                <dt>{{ $item['day'] ?? 'Tag' }}</dt>
                <dd>
                    <strong>{{ ! empty($item['closed']) ? 'Geschlossen' : ($item['hours'] ?? 'Keine Zeiten hinterlegt') }}</strong>
                    @if (filled($item['note'] ?? null))
                        <span>{{ $item['note'] }}</span>
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>
</section>
