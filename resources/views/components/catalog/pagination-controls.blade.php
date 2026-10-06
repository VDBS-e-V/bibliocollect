@props([
    'paginator',
    'queryParameters' => [],
    'routeName',
    'idPrefix' => 'catalog-pagination',
])

@php
    $preservedQuery = collect($queryParameters)
        ->except(['page', 'per_page'])
        ->filter(static fn ($value) => $value !== null && $value !== '')
        ->all();
    $perPageOptions = [10, 20, 50, 100];
@endphp

@if ($paginator->total() > 0)
    <nav class="bc-catalog-pagination" aria-label="Seitennavigation der Katalogtreffer">
        <p class="bc-catalog-pagination__summary">
            <strong>Ergebnisse:</strong>
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} von {{ $paginator->total() }}
        </p>

        <form method="get" action="{{ route($routeName) }}" class="bc-catalog-pagination__per-page">
            @foreach ($preservedQuery as $name => $value)
                @if (is_scalar($value))
                    <input type="hidden" name="{{ $name }}" value="{{ is_bool($value) ? ($value ? '1' : '0') : $value }}">
                @endif
            @endforeach

            <label for="{{ $idPrefix }}-per-page">Ergebnisse pro Seite</label>
            <select id="{{ $idPrefix }}-per-page" name="per_page" data-auto-submit>
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}" @selected($paginator->perPage() === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <noscript><button type="submit">Anzeigen</button></noscript>
        </form>

        <div class="bc-catalog-pagination__pager">
            @if ($paginator->onFirstPage())
                <span class="bc-catalog-pagination__arrow" aria-disabled="true" aria-label="Keine vorherige Seite">←</span>
            @else
                <a class="bc-catalog-pagination__arrow" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Vorherige Seite">←</a>
            @endif

            <form method="get" action="{{ route($routeName) }}" class="bc-catalog-pagination__page-form">
                @foreach ($preservedQuery as $name => $value)
                    @if (is_scalar($value))
                        <input type="hidden" name="{{ $name }}" value="{{ is_bool($value) ? ($value ? '1' : '0') : $value }}">
                    @endif
                @endforeach
                <input type="hidden" name="per_page" value="{{ $paginator->perPage() }}">

                <label for="{{ $idPrefix }}-page">Seite</label>
                <input
                    id="{{ $idPrefix }}-page"
                    name="page"
                    type="number"
                    min="1"
                    max="{{ $paginator->lastPage() }}"
                    value="{{ $paginator->currentPage() }}"
                    inputmode="numeric"
                >
                <span>von {{ $paginator->lastPage() }}</span>
                <button type="submit">Los</button>
            </form>

            @if ($paginator->hasMorePages())
                <a class="bc-catalog-pagination__arrow" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Nächste Seite">→</a>
            @else
                <span class="bc-catalog-pagination__arrow" aria-disabled="true" aria-label="Keine nächste Seite">→</span>
            @endif
        </div>
    </nav>
@endif
