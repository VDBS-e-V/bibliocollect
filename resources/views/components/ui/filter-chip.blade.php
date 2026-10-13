@props([
    'label',
    'href' => null,
    'active' => false,
    'removable' => false,
])

@if (filled($href))
    <a href="{{ $href }}" {{ $attributes->class([
        'bc-filter-chip',
        'bc-filter-chip--active' => $active,
    ]) }} @if ($removable) aria-label="{{ $label }} entfernen" @endif>
        <span>{{ $label }}</span>
        @if ($removable)<span class="bc-filter-chip__remove" aria-hidden="true">×</span>@endif
    </a>
@else
    <span {{ $attributes->class([
        'bc-filter-chip',
        'bc-filter-chip--active' => $active,
    ]) }}>
        {{ $label }}
    </span>
@endif
