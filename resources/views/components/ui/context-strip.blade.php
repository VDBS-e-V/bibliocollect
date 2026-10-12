@props([
    'title',
    'items' => [],
    'backHref' => null,
    'backLabel' => 'Zurück zur Übersicht',
    'label' => 'Detailinformationen',
    'density' => 'comfortable',
])

@php($density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable')

<section {{ $attributes->class('bc-context-strip bc-context-strip--'.$density) }} aria-label="{{ $label }}">
    <div class="bc-context-strip__identity">
        @if (filled($backHref))
            <a class="bc-context-strip__back" href="{{ $backHref }}"><span aria-hidden="true">←</span> {{ $backLabel }}</a>
        @endif
        <strong class="bc-context-strip__title">{{ $title }}</strong>
    </div>
    @if (count($items) > 0)
        <dl class="bc-context-strip__facts">
            @foreach ($items as $item)
                <div>
                    <dt>{{ $item['label'] ?? 'Angabe' }}</dt>
                    <dd>{{ $item['value'] ?? '—' }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
    @isset($status)
        <div class="bc-context-strip__status">{{ $status }}</div>
    @endisset
    @isset($actions)
        <div class="bc-context-strip__actions">{{ $actions }}</div>
    @endisset
</section>
