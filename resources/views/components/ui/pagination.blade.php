@props([
    'previous' => null,
    'next' => null,
    'current' => 1,
    'total' => 1,
    'label' => 'Seitennavigation',
])

<nav {{ $attributes->class('bc-pagination') }} aria-label="{{ $label }}">
    @if (filled($previous))
        <a class="bc-pagination__control" href="{{ $previous }}">← Zurück</a>
    @else
        <span class="bc-pagination__control bc-pagination__control--disabled" aria-disabled="true">← Zurück</span>
    @endif
    <span class="bc-pagination__current">Seite {{ max(1, (int) $current) }} von {{ max(1, (int) $total) }}</span>
    @if (filled($next))
        <a class="bc-pagination__control" href="{{ $next }}">Weiter →</a>
    @else
        <span class="bc-pagination__control bc-pagination__control--disabled" aria-disabled="true">Weiter →</span>
    @endif
</nav>
