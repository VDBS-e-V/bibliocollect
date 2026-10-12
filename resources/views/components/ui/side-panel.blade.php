@props([
    'id', 'title', 'description' => null, 'closeLabel' => 'Schließen',
    'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $titleId = $id.'-title';
    $descriptionId = $id.'-description';
@endphp
<dialog {{ $attributes->class('bc-ui-dialog bc-side-panel bc-side-panel--'.$density)->merge([
    'id' => $id, 'aria-labelledby' => $titleId,
    'aria-describedby' => $description ? $descriptionId : null,
]) }}>
    <header class="bc-ui-dialog__header">
        <h2 id="{{ $titleId }}">{{ $title }}</h2>
        <button type="button" class="bc-ui-dialog__close" data-ui-dialog-close aria-label="{{ $closeLabel }}">×</button>
    </header>
    @if ($description) <p class="bc-ui-dialog__description" id="{{ $descriptionId }}">{{ $description }}</p> @endif
    <div class="bc-ui-dialog__body bc-side-panel__content">{{ $slot }}</div>
    <footer class="bc-ui-dialog__footer">
        @isset($actions)
            {{ $actions }}
        @else
            <x-ui.button variant="secondary" data-ui-dialog-close>{{ $closeLabel }}</x-ui.button>
        @endisset
    </footer>
</dialog>
