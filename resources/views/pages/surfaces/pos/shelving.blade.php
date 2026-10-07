<x-app-shell surface="pos" title="Medien einsortieren">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Medien einsortieren"
        lead="Neu erfasste Bücher liegen auf einem Stapel. Wähle das Regalbrett, scanne die Bücher und stelle sie dort ein. Der Standort wird dabei im System vermerkt."
    />

    @if (session('shelving_notice'))
        <x-ui.alert variant="success" title="Einsortiert">{{ session('shelving_notice') }}</x-ui.alert>
    @endif

    @if (session('shelving_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('shelving_error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-work-panel" aria-labelledby="shelf-pick-heading">
        <div class="bc-section-heading"><h2 id="shelf-pick-heading">1. Regalbrett wählen</h2></div>
        @if ($shelfOptions === [])
            <p class="bc-section-copy">Es sind noch keine Regalbretter angelegt. Die Verwaltung legt sie unter „Regalbretter“ an.</p>
        @else
            <form method="get" action="{{ route('pos.shelving') }}" class="bc-audit-filter">
                <x-ui.select label="Ich räume ein auf" name="regalbrett" id="shelving-shelf" data-auto-submit>
                    <option value="">Bitte wählen …</option>
                    @foreach ($shelfOptions as $code => $display)
                        <option value="{{ $code }}" @selected($shelf === $code)>{{ $display }}</option>
                    @endforeach
                </x-ui.select>
                <noscript><x-ui.button type="submit" variant="secondary">Wählen</x-ui.button></noscript>
            </form>
        @endif
    </section>

    @if ($shelf !== '')
        <section class="bc-work-panel" aria-labelledby="shelf-scan-heading">
            <div class="bc-section-heading"><h2 id="shelf-scan-heading">2. Bücher scannen</h2></div>
            <form method="post" action="{{ route('pos.shelving.scan') }}" class="bc-pos-scan">
                @csrf
                <input type="hidden" name="regalbrett" value="{{ $shelf }}">
                <x-ui.input
                    label="Inventarnummer des Buchs für {{ $shelf }}"
                    name="code"
                    id="shelving-code"
                    hint="Nach jedem Scan ist das Buch eingetragen; gleich das nächste scannen."
                    autocomplete="off"
                    autofocus
                />
                <x-ui.button type="submit">Einsortieren</x-ui.button>
            </form>
        </section>
    @endif

    <section class="bc-content-section" aria-labelledby="stack-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="stack-heading">Stapel „Einsortieren“</h2>
            <span>{{ $stackTotal }}</span>
        </div>

        @if ($stack->isEmpty())
            <p class="bc-section-copy">Der Stapel ist leer. Alles ist einsortiert.</p>
        @else
            <table class="bc-calendar-table">
                <thead>
                    <tr><th scope="col">Inventarnummer</th><th scope="col">Titel</th><th scope="col">Erfasst am</th></tr>
                </thead>
                <tbody>
                    @foreach ($stack as $copy)
                        <tr>
                            <th scope="row" class="bc-tabular">{{ $copy->barcode }}</th>
                            <td>{{ $copy->edition->title->preferred_title }}</td>
                            <td class="bc-tabular">{{ $copy->created_at?->format('d.m.Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($stackTotal > $stack->count())
                <p class="bc-section-copy">Es werden die ersten {{ $stack->count() }} gezeigt.</p>
            @endif
        @endif
    </section>

    @if ($recent->isNotEmpty())
        <section class="bc-content-section" aria-labelledby="recent-heading">
            <div class="bc-section-heading"><h2 id="recent-heading">Zuletzt einsortiert</h2></div>
            <table class="bc-calendar-table">
                <thead>
                    <tr><th scope="col">Inventarnummer</th><th scope="col">Titel</th><th scope="col">Regalbrett</th></tr>
                </thead>
                <tbody>
                    @foreach ($recent as $copy)
                        <tr>
                            <th scope="row" class="bc-tabular">{{ $copy->barcode }}</th>
                            <td>{{ $copy->edition->title->preferred_title }}</td>
                            <td>{{ $copy->shelf_location }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
</x-app-shell>
