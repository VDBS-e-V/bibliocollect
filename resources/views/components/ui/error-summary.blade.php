@props([
    'title' => 'Bitte überprüfe deine Angaben.',
    'items' => [],
])

@php
    $id = (string) $attributes->get('id', 'validation-summary');
@endphp

<div {{ $attributes->class('bc-error-summary')->merge([
    'id' => $id,
    'role' => 'alert',
    'aria-labelledby' => $id.'-title',
]) }}>
    <h3 class="bc-error-summary__title" id="{{ $id }}-title">{{ $title }}</h3>
    @if (count($items) > 0)
        <ul class="bc-error-summary__list">
            @foreach ($items as $item)
                <li>
                    @if (filled($item['href'] ?? null))
                        <a href="{{ $item['href'] }}">{{ $item['label'] ?? 'Angabe überprüfen' }}</a>
                    @else
                        {{ $item['label'] ?? 'Angabe überprüfen' }}
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
