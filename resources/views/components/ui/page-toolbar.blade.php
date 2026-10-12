@props([
    'title',
    'description' => null,
    'density' => 'comfortable',
])

@php($density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable')

<header {{ $attributes->class('bc-page-toolbar bc-page-toolbar--'.$density) }}>
    <div class="bc-page-toolbar__copy">
        <strong class="bc-page-toolbar__title">{{ $title }}</strong>
        @if (filled($description))
            <p class="bc-page-toolbar__description">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="bc-page-toolbar__actions">{{ $actions }}</div>
    @endisset
</header>
