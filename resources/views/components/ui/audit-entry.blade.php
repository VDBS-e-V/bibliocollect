@props([
    'title',
    'actor' => null,
    'occurredAt' => null,
    'dateLabel' => null,
    'kind' => 'updated',
    'description' => null,
    'changes' => [],
    'href' => null,
    'linkLabel' => 'Eintrag ansehen',
    'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $kinds = ['created' => 'Erstellt', 'updated' => 'Geändert', 'deleted' => 'Entfernt', 'restored' => 'Wiederhergestellt', 'access' => 'Zugriff', 'system' => 'Systemereignis'];
    $kind = array_key_exists($kind, $kinds) ? $kind : 'system';
@endphp

<article {{ $attributes->class('bc-audit-entry bc-audit-entry--'.$density) }}>
    <header class="bc-audit-entry__header">
        <div class="bc-audit-entry__heading">
            <span class="bc-audit-entry__kind bc-audit-entry__kind--{{ $kind }}">{{ $kinds[$kind] }}</span>
            <h3>{{ $title }}</h3>
        </div>
        @if (filled($occurredAt))
            <time datetime="{{ $occurredAt }}">{{ $dateLabel ?? $occurredAt }}</time>
        @elseif (filled($dateLabel))
            <span class="bc-audit-entry__date">{{ $dateLabel }}</span>
        @endif
    </header>
    @if (filled($actor)) <p class="bc-audit-entry__actor">Ausgeführt von: <strong>{{ $actor }}</strong></p> @endif
    @if (filled($description)) <p class="bc-audit-entry__description">{{ $description }}</p> @endif
    @if (count($changes) > 0)
        <x-ui.change-review :items="$changes" title="Dokumentierte Änderungen" density="compact" />
    @endif
    @if (filled($href)) <a class="bc-audit-entry__link" href="{{ $href }}">{{ $linkLabel }}</a> @endif
</article>
