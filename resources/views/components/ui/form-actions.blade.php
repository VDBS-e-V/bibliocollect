@props([
    'submitLabel' => 'Speichern', 'cancelHref' => null, 'backHref' => null,
    'status' => null, 'disabled' => false, 'density' => 'comfortable',
])
@php $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable'; @endphp
<div {{ $attributes->class('bc-form-actions bc-form-actions--'.$density) }}>
    @if (filled($status)) <p class="bc-form-actions__status" role="status">{{ $status }}</p> @endif
    <div class="bc-form-actions__row">
        <div class="bc-form-actions__secondary">
            @if (filled($backHref)) <x-ui.button variant="tertiary" :href="$backHref">Zurück</x-ui.button> @endif
            @if (filled($cancelHref)) <x-ui.button variant="secondary" :href="$cancelHref">Abbrechen</x-ui.button> @endif
            @isset($secondary) {{ $secondary }} @endisset
        </div>
        <div class="bc-form-actions__primary">
            @isset($primary)
                {{ $primary }}
            @else
                <x-ui.button type="submit" :disabled="$disabled">{{ $submitLabel }}</x-ui.button>
            @endisset
        </div>
    </div>
</div>
