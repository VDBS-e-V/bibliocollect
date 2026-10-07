<x-app-shell surface="pos" title="Inventur">
    <x-ui.page-header
        kicker="Katalog und Bestand"
        title="Inventur"
        lead="Bestand und Regal abgleichen: Regalbrett wählen, die Bücher dort scannen, am Ende zeigt der Bericht, was fehlt, falsch steht oder unbekannt ist."
    />

    @if ($errors->any())
        <x-ui.alert variant="error" title="Nicht möglich">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if ($open)
        <section class="bc-work-panel" aria-labelledby="open-heading">
            <div class="bc-section-heading"><h2 id="open-heading">Laufende Inventur</h2></div>
            <p class="bc-section-copy"><strong>{{ $open->name }}</strong>, begonnen am {{ $open->started_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}, {{ $open->items_count }} Bücher gescannt.</p>
            <x-ui.button href="{{ route('pos.inventory.show', ['countId' => $open->getKey()]) }}">Weiter zählen</x-ui.button>
        </section>
    @else
        <section class="bc-work-panel" aria-labelledby="start-heading">
            <div class="bc-section-heading"><h2 id="start-heading">Neue Inventur beginnen</h2></div>
            <form method="post" action="{{ route('pos.inventory.start') }}" class="bc-audit-filter">
                @csrf
                <x-ui.input label="Name (freiwillig)" name="name" id="inventory-name" maxlength="120" hint="Zum Beispiel „Inventur Fantasy-Regal“. Ohne Name steht das Datum." />
                <x-ui.button type="submit">Inventur beginnen</x-ui.button>
            </form>
        </section>
    @endif

    @if ($closed->isNotEmpty())
        <section class="bc-content-section" aria-labelledby="closed-heading">
            <div class="bc-section-heading"><h2 id="closed-heading">Abgeschlossene Inventuren</h2></div>
            <table class="bc-calendar-table">
                <thead><tr><th scope="col">Inventur</th><th scope="col">Abgeschlossen</th><th scope="col">Gescannt</th></tr></thead>
                <tbody>
                    @foreach ($closed as $count)
                        <tr>
                            <th scope="row"><a href="{{ route('pos.inventory.report', ['countId' => $count->getKey()]) }}">{{ $count->name }}</a></th>
                            <td class="bc-tabular">{{ $count->closed_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</td>
                            <td class="bc-tabular">{{ $count->items_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
</x-app-shell>
