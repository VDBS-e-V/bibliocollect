@props([
    'title' => 'Keine Ergebnisse',
    'description' => null,
    'query' => null,
    'state' => 'empty',
    'suggestions' => [],
    'label' => 'Hilfe zu den Ergebnissen',
    'density' => 'comfortable',
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $state = in_array($state, ['empty', 'error', 'unavailable'], true) ? $state : 'empty';
@endphp

<section {{ $attributes->class('bc-result-assistance bc-result-assistance--'.$density.' bc-result-assistance--'.$state) }} aria-label="{{ $label }}">
    @if (filled($query))
        <p class="bc-result-assistance__query">Suchbegriff: <strong>{{ $query }}</strong></p>
    @endif

    @if ($state === 'empty')
        <x-ui.empty-state :title="$title" :description="$description" tone="brand" :compact="$density === 'compact'">
            @isset($actions)
                <x-slot:actions>{{ $actions }}</x-slot:actions>
            @endisset
        </x-ui.empty-state>
    @else
        <x-ui.alert :variant="$state === 'error' ? 'error' : 'warning'" :title="$title">
            {{ $description ?? 'Dieser Bereich steht momentan nicht zur Verfügung.' }}
        </x-ui.alert>
        @isset($actions)
            <div class="bc-result-assistance__actions">{{ $actions }}</div>
        @endisset
    @endif

    @if (count($suggestions) > 0)
        <nav class="bc-result-assistance__suggestions" aria-label="Alternative Suchwege">
            <strong>Weitere Möglichkeiten</strong>
            <ul>
                @foreach ($suggestions as $suggestion)
                    @if (filled($suggestion['label'] ?? null))
                        <li>
                            @if (filled($suggestion['href'] ?? null) && ! ($suggestion['disabled'] ?? false))
                                <a href="{{ $suggestion['href'] }}">{{ $suggestion['label'] }} <span aria-hidden="true">→</span></a>
                            @else
                                <span class="bc-result-assistance__inactive" aria-disabled="true">{{ $suggestion['label'] }}</span>
                            @endif
                        </li>
                    @endif
                @endforeach
            </ul>
        </nav>
    @endif
</section>
