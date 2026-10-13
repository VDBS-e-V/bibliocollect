@props([
    'items' => [],
    'density' => 'comfortable',
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
@endphp

<dl {{ $attributes->class('bc-description-list bc-description-list--'.$density) }}>
    @foreach ($items as $item)
        <div class="bc-description-list__item">
            <dt>{{ $item['label'] ?? 'Angabe' }}</dt>
            <dd>{{ $item['value'] ?? '—' }}</dd>
        </div>
    @endforeach
</dl>
