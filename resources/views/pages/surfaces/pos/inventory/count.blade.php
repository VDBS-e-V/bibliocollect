<x-app-shell surface="pos" :title="$count->name">
    <x-ui.page-header kicker="Inventur" :title="$count->name" lead="Regalbrett wählen, dann die Inventarnummern der Bücher scannen, die dort stehen." />

    <div class="bc-context-actions">
        <a href="{{ route('pos.inventory') }}">← Zur Übersicht</a>
        <a href="{{ route('pos.inventory.report', ['countId' => $count->getKey()]) }}">Zwischenstand ansehen</a>
    </div>

    @if (session('inventory_notice'))
        <x-ui.alert variant="success" title="Gezählt">{{ session('inventory_notice') }}</x-ui.alert>
    @endif
    @if (session('inventory_warning'))
        <x-ui.alert title="Achtung">{{ session('inventory_warning') }}</x-ui.alert>
    @endif
    @if (session('inventory_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('inventory_error') }}</x-ui.alert>
    @endif
    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if ($count->isOpen())
        <section class="bc-work-panel" aria-labelledby="inventory-scan-heading">
            <div class="bc-section-heading"><h2 id="inventory-scan-heading">Scannen</h2></div>
            @if ($shelfOptions === [])
                <p class="bc-section-copy">Es sind noch keine Regalbretter angelegt.</p>
            @else
                <form method="get" action="{{ route('pos.inventory.show', ['countId' => $count->getKey()]) }}" class="bc-audit-filter">
                    <x-ui.select label="Ich stehe am Regalbrett" name="regalbrett" id="inventory-shelf" data-auto-submit>
                        <option value="">Bitte wählen …</option>
                        @foreach ($shelfOptions as $code => $display)
                            <option value="{{ $code }}" @selected($shelf === $code)>{{ $display }}</option>
                        @endforeach
                    </x-ui.select>
                    <noscript><x-ui.button type="submit" variant="secondary">Wählen</x-ui.button></noscript>
                </form>

                @if ($shelf !== '')
                    <form method="post" action="{{ route('pos.inventory.scan', ['countId' => $count->getKey()]) }}" class="bc-pos-scan">
                        @csrf
                        <input type="hidden" name="regalbrett" value="{{ $shelf }}">
                        <x-ui.input label="Inventarnummer des Buchs auf {{ $shelf }}" name="code" id="inventory-code" autocomplete="off" inputmode="numeric" autofocus />
                        <x-ui.button type="submit">Zählen</x-ui.button>
                    </form>
                @endif
            @endif
        </section>
    @endif

    @if ($perShelf !== [])
        <section class="bc-content-section" aria-labelledby="per-shelf-heading">
            <div class="bc-section-heading"><h2 id="per-shelf-heading">Bisher gezählt</h2></div>
            <table class="bc-calendar-table">
                <thead><tr><th scope="col">Regalbrett</th><th scope="col">Bücher</th></tr></thead>
                <tbody>
                    @foreach ($perShelf as $code => $total)
                        <tr><th scope="row">{{ $code }}</th><td class="bc-tabular">{{ $total }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    @if ($recent->isNotEmpty())
        <section class="bc-content-section" aria-labelledby="recent-heading">
            <div class="bc-section-heading"><h2 id="recent-heading">Zuletzt gescannt</h2></div>
            <table class="bc-calendar-table">
                <thead><tr><th scope="col">Inventarnummer</th><th scope="col">Titel</th><th scope="col">Regalbrett</th></tr></thead>
                <tbody>
                    @foreach ($recent as $item)
                        <tr>
                            <th scope="row" class="bc-tabular">{{ $item->barcode }}</th>
                            <td>{{ $item->copy?->edition->title->preferred_title ?? 'unbekannt' }}</td>
                            <td>{{ $item->shelf_code }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    @if ($count->isOpen() && $perShelf !== [])
        <section class="bc-content-section" aria-labelledby="close-heading">
            <div class="bc-section-heading"><h2 id="close-heading">Fertig?</h2></div>
            <p class="bc-section-copy">Beim Abschließen wird der Bestand mit dem Gezählten abgeglichen. Geprüft sind nur die Regalbretter, an denen du gescannt hast.</p>
            <form method="post" action="{{ route('pos.inventory.close', ['countId' => $count->getKey()]) }}">
                @csrf
                <x-ui.button type="submit" variant="secondary" data-confirm="Die Inventur abschließen und den Bericht erstellen?">Inventur abschließen</x-ui.button>
            </form>
        </section>
    @endif
</x-app-shell>
