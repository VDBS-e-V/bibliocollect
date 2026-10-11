@props([
    'kicker' => null,
    'title',
    'lead' => null,
    'actions' => null,
])

@php($hasActions = $actions !== null && ! $actions->isEmpty())

<header {{ $attributes->class(['bc-page-header', 'bc-page-header--with-actions' => $hasActions]) }}>
    @if ($hasActions)
        <div class="bc-page-header__main">
    @endif
    @if ($kicker)
        <p class="bc-page-header__kicker">{{ $kicker }}</p>
    @endif
    <h1 class="bc-page-header__title">{{ $title }}</h1>
    @if ($lead)
        <p class="bc-page-header__lead">{{ $lead }}</p>
    @endif
    @if ($hasActions)
        </div>
        <div class="bc-page-header__actions">{{ $actions }}</div>
    @endif
</header>
