@props([
    'href',
    'title',
    'description',
    'icon' => 'book',
])

<a href="{{ $href }}" {{ $attributes->class('bc-library-quick-action') }}>
    <span class="bc-library-quick-action__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
            @switch($icon)
                @case('search')
                    <circle cx="11" cy="11" r="7" />
                    <path d="m20 20-4-4" />
                    @break
                @case('heart')
                    <path d="M20 8c0 5-8 11-8 11S4 13 4 8a4 4 0 0 1 8-1 4 4 0 0 1 8 1Z" />
                    @break
                @case('user')
                    <circle cx="12" cy="8" r="4" />
                    <path d="M4 21a8 8 0 0 1 16 0" />
                    @break
                @default
                    <path d="M12 7a6 6 0 0 0-8-1v13a6 6 0 0 1 8 1 6 6 0 0 1 8-1V6a6 6 0 0 0-8 1Zm0 0v13" />
            @endswitch
        </svg>
    </span>
    <strong>{{ $title }}</strong>
    <span class="bc-library-quick-action__description">{{ $description }}</span>
    <svg class="bc-library-quick-action__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        <path d="M7 17 17 7M7 7h10v10" />
    </svg>
</a>
