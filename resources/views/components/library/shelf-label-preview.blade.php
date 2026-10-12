@props([
    'signature',
    'shelf' => null,
    'subtitle' => null,
    'type' => 'shelf',
])

@php
    $type = in_array($type, ['shelf', 'spine'], true) ? $type : 'shelf';
@endphp

<div {{ $attributes->class('bc-shelf-label-preview bc-shelf-label-preview--'.$type) }}>
    <span class="bc-shelf-label-preview__eyebrow">BIBLIOCOLLECT · VDBS</span>
    <strong class="bc-shelf-label-preview__signature">{{ $signature }}</strong>
    @if (filled($shelf))
        <span class="bc-shelf-label-preview__shelf">{{ $shelf }}</span>
    @endif
    @if (filled($subtitle))
        <span class="bc-shelf-label-preview__subtitle">{{ $subtitle }}</span>
    @endif
</div>
