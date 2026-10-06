<x-app-shell surface="pos" title="Etiketten drucken">
    <x-ui.page-header
        kicker="Katalogpflege"
        title="Exemplar-Etiketten drucken"
        :lead="'Strichcode, Signatur und Kurztitel auf Bögen mit '.$perSheet.' Etiketten (63,5 × 38,1 mm, z. B. Avery L7160). Auf „Tatsächliche Größe“ drucken, ohne Seitenanpassung.'"
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.catalog.index') }}">← Zurück zur Katalogpflege</a>
        <a href="{{ route('pos.labels.copies', ['neu' => 1]) }}">Zuletzt erfasste Exemplare</a>
    </div>

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="get" action="{{ route('pos.labels.copies') }}" class="bc-audit-filter" role="search">
        @if ($editionId !== '')<input type="hidden" name="ausgabe" value="{{ $editionId }}">@endif
        <x-ui.input label="Suche (Barcode, ISBN oder Titel)" name="q" :value="$term" />
        <x-ui.button type="submit" variant="secondary">Suchen</x-ui.button>
    </form>

    <form method="post" action="{{ route('pos.labels.copies.print') }}" target="_blank">
        @csrf
        <section class="bc-content-section" aria-labelledby="label-copies-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="label-copies-heading">{{ $recent ? 'Zuletzt erfasst' : 'Exemplare' }}</h2>
                <span>{{ $copies->count() }}</span>
            </div>

            @if ($copies->isEmpty())
                <p class="bc-section-copy">Keine Exemplare gefunden.</p>
            @else
                <table class="bc-calendar-table">
                    <thead>
                        <tr>
                            <th scope="col"><label><input type="checkbox" data-select-all="copies[]"> <span class="bc-visually-hidden">Alle auswählen</span></label></th>
                            <th scope="col">Barcode</th>
                            <th scope="col">Titel</th>
                            <th scope="col">Standort</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($copies as $copy)
                            <tr>
                                <td><input type="checkbox" name="copies[]" value="{{ $copy->getKey() }}" aria-label="{{ $copy->barcode }} auswählen" @checked($recent || $editionId !== '')></td>
                                <th scope="row" class="bc-tabular">{{ $copy->barcode }}</th>
                                <td>{{ $copy->edition->title->preferred_title }}</td>
                                <td>{{ $copy->shelf_location ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="bc-audit-filter">
                    <x-ui.input label="Erstes freies Etikett auf dem Bogen (1–{{ $perSheet }})" name="start" type="number" min="1" :max="$perSheet" value="1" />
                    <x-ui.button type="submit">Etiketten ansehen und drucken</x-ui.button>
                </div>
            @endif
        </section>
    </form>
</x-app-shell>
