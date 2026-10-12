@props([
    'previous' => null,
    'next' => null,
    'cancel' => null,
    'previousLabel' => 'Zurück',
    'nextLabel' => 'Weiter',
    'cancelLabel' => 'Abbrechen',
    'label' => 'Schrittnavigation',
    'density' => 'comfortable',
])

@php($density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable')

<nav {{ $attributes->class('bc-step-actions bc-step-actions--'.$density) }} aria-label="{{ $label }}">
    <div class="bc-step-actions__secondary">
        @if (filled($cancel))
            <a class="bc-step-actions__cancel" href="{{ $cancel }}">{{ $cancelLabel }}</a>
        @endif
    </div>
    <div class="bc-step-actions__movement">
        @if (filled($previous))
            <a class="bc-step-actions__control bc-step-actions__control--previous" href="{{ $previous }}"><span aria-hidden="true">←</span> {{ $previousLabel }}</a>
        @else
            <span class="bc-step-actions__control bc-step-actions__control--disabled" aria-disabled="true"><span aria-hidden="true">←</span> {{ $previousLabel }}</span>
        @endif
        @if (filled($next))
            <a class="bc-step-actions__control bc-step-actions__control--next" href="{{ $next }}">{{ $nextLabel }} <span aria-hidden="true">→</span></a>
        @else
            <span class="bc-step-actions__control bc-step-actions__control--disabled" aria-disabled="true">{{ $nextLabel }} <span aria-hidden="true">→</span></span>
        @endif
    </div>
</nav>
