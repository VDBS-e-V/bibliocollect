@props([
    'action' => null,
    'id' => 'library-catalog-search',
    'query' => '',
    'placeholder' => 'Titel, Autor:in oder Thema suchen …',
])

<form method="get" action="{{ $action ?? route('public.catalog.index') }}" {{ $attributes->class('bc-library-search') }} role="search" aria-label="Katalog durchsuchen">
    <label class="sr-only" for="{{ $id }}">Suchbegriff</label>
    <svg class="bc-library-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false">
        <circle cx="11" cy="11" r="7" />
        <path d="m20 20-4-4" />
    </svg>
    <input
        id="{{ $id }}"
        name="q"
        type="search"
        value="{{ $query }}"
        placeholder="{{ $placeholder }}"
        minlength="2"
        maxlength="120"
        autocomplete="off"
    >
    <x-ui.button type="submit">Suchen</x-ui.button>
</form>
