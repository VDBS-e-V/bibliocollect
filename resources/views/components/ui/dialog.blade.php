@props([
    'id',
    'title',
    'description' => null,
    'closeLabel' => 'Schließen',
    'tone' => 'default',
])

@php
    $tone = in_array($tone, ['default', 'warning'], true) ? $tone : 'default';
    $titleId = $id.'-title';
    $descriptionId = $id.'-description';
@endphp

<dialog {{ $attributes->class('bc-ui-dialog bc-ui-dialog--'.$tone)->merge([
    'id' => $id,
    'aria-labelledby' => $titleId,
    'aria-describedby' => $description ? $descriptionId : null,
]) }}>
    <header class="bc-ui-dialog__header">
        <h2 id="{{ $titleId }}">{{ $title }}</h2>
        <button type="button" class="bc-ui-dialog__close" data-ui-dialog-close aria-label="{{ $closeLabel }}">×</button>
    </header>
    @if ($description)
        <p class="bc-ui-dialog__description" id="{{ $descriptionId }}">{{ $description }}</p>
    @endif
    <div class="bc-ui-dialog__body">{{ $slot }}</div>
    <footer class="bc-ui-dialog__footer">
        @isset($actions)
            {{ $actions }}
        @else
            <button type="button" class="bc-button bc-button--secondary" data-ui-dialog-close>{{ $closeLabel }}</button>
        @endisset
    </footer>
</dialog>
