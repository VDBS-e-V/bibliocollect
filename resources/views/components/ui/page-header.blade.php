@props([
    'kicker' => null,
    'title',
    'lead' => null,
])

<header {{ $attributes->class('bc-page-header') }}>
    @if ($kicker)
        <p class="bc-page-header__kicker">{{ $kicker }}</p>
    @endif
    <h1 class="bc-page-header__title">{{ $title }}</h1>
    @if ($lead)
        <p class="bc-page-header__lead">{{ $lead }}</p>
    @endif
</header>
