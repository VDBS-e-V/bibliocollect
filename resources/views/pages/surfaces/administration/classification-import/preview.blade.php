@php
    $labels = ['new' => 'Neu', 'same' => 'Unverändert', 'changed' => 'Abweichung'];
    $variants = ['new' => 'success', 'same' => 'neutral', 'changed' => 'warning'];
    $counts = $plan->counts;
@endphp

<x-app-shell surface="administration" title="Import prüfen">
    <x-ui.page-header
        kicker="Themen & Regalbretter importieren"
        title="Vorschau"
        lead="Hier ist noch nichts geschrieben. Prüfe, was der Import tun würde, und bestätige ihn unten."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.classification-import.index') }}">← Andere Dateien wählen</a>
    </div>

    @if (session('import_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('import_error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Bitte prüfen">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="summary-heading">
        <div class="bc-section-heading"><h2 id="summary-heading">Zusammenfassung</h2></div>
        <table class="bc-calendar-table">
            <thead><tr><th scope="col">Kennzahl</th><th scope="col">Anzahl</th></tr></thead>
            <tbody>
                <tr><th scope="row">Neue Themen</th><td>{{ $counts['new_topics'] }}</td></tr>
                <tr><th scope="row">Bestehende Themen</th><td>{{ $counts['existing_topics'] }}</td></tr>
                <tr><th scope="row">Themenkonflikte (Abweichungen vom Bestand)</th><td>{{ $counts['conflicts'] }}</td></tr>
                <tr><th scope="row">Neue Signaturen</th><td>{{ $counts['new_signatures'] }}</td></tr>
                <tr><th scope="row">Bestehende Signaturen</th><td>{{ $counts['existing_signatures'] }}</td></tr>
                <tr><th scope="row">Neue Regalbretter</th><td>{{ $counts['new_shelves'] }}</td></tr>
                <tr><th scope="row">Bestehende Regalbretter</th><td>{{ $counts['existing_shelves'] }}</td></tr>
                <tr><th scope="row">Neue Themenzuordnungen (Regalbretter)</th><td>{{ $counts['new_assignments'] }}</td></tr>
                <tr><th scope="row">Neue Themenzuordnungen (Signaturen)</th><td>{{ $counts['new_signature_assignments'] }}</td></tr>
                <tr><th scope="row">Warnungen</th><td>{{ $counts['warnings'] }}</td></tr>
                <tr><th scope="row">Fehler</th><td>{{ $counts['errors'] }}</td></tr>
            </tbody>
        </table>
    </section>

    @if ($plan->errors !== [])
        <section class="bc-content-section" aria-labelledby="errors-heading">
            <div class="bc-section-heading bc-section-heading--with-meta"><h2 id="errors-heading">Fehler (der Import ist so nicht möglich)</h2><span>{{ count($plan->errors) }}</span></div>
            <ul class="bc-section-copy">
                @foreach (array_slice($plan->errors, 0, 100) as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            @if (count($plan->errors) > 100)<p class="bc-section-copy">… und {{ count($plan->errors) - 100 }} weitere.</p>@endif
        </section>
    @endif

    @if ($plan->conflicts !== [])
        <section class="bc-content-section" aria-labelledby="conflicts-heading">
            <div class="bc-section-heading bc-section-heading--with-meta"><h2 id="conflicts-heading">Abweichungen vom Bestand</h2><span>{{ count($plan->conflicts) }}</span></div>
            <p class="bc-section-copy">Diese Themen gibt es schon, aber mit anderen Angaben. Sie werden nur geändert, wenn du unten ausdrücklich „Abweichungen übernehmen“ wählst.</p>
            <ul class="bc-section-copy">
                @foreach (array_slice($plan->conflicts, 0, 100) as $conflict)
                    <li>{{ $conflict }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($plan->warnings !== [])
        <section class="bc-content-section" aria-labelledby="warnings-heading">
            <div class="bc-section-heading bc-section-heading--with-meta"><h2 id="warnings-heading">Warnungen</h2><span>{{ count($plan->warnings) }}</span></div>
            <ul class="bc-section-copy">
                @foreach (array_slice($plan->warnings, 0, 100) as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($plan->topics !== [])
        <section class="bc-content-section" aria-labelledby="topics-heading">
            <div class="bc-section-heading bc-section-heading--with-meta"><h2 id="topics-heading">Themen</h2><span>{{ count($plan->topics) }}</span></div>
            <div class="bc-table-wrap">
                <table class="bc-table">
                    <caption class="bc-visually-hidden">Themen der Importdatei mit Stand im Bestand</caption>
                    <thead><tr><th scope="col">ID</th><th scope="col">Schlüssel</th><th scope="col">Thema</th><th scope="col">Elternthema</th><th scope="col">Stand</th></tr></thead>
                    <tbody>
                        @foreach (array_slice($plan->topics, 0, 400) as $topic)
                            <tr>
                                <td>{{ $topic['legacy_id'] }}</td>
                                <td>{{ $topic['public_key'] ?? '–' }}</td>
                                <td>{{ $topic['name'] }}</td>
                                <td>{{ $topic['parent'] !== null ? ($topicNames[$topic['parent']] ?? 'ID '.$topic['parent']) : '–' }}</td>
                                <td>
                                    <x-ui.badge :variant="$variants[$topic['status']]">{{ $labels[$topic['status']] }}</x-ui.badge>
                                    @foreach ($topic['changes'] as $change)<small class="bc-public-metadata-source">{{ $change }}</small>@endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($plan->signatures !== [])
        <section class="bc-content-section" aria-labelledby="signatures-heading">
            <div class="bc-section-heading bc-section-heading--with-meta"><h2 id="signatures-heading">Regalbretter</h2><span>{{ count($plan->signatures) }}</span></div>
            <div class="bc-table-wrap">
                <table class="bc-table">
                    <caption class="bc-visually-hidden">Regalsignaturen der Importdatei mit Stand im Bestand</caption>
                    <thead><tr><th scope="col">Signatur</th><th scope="col">Themen</th><th scope="col">Regalbrett</th><th scope="col">Neue Zuordnungen</th></tr></thead>
                    <tbody>
                        @foreach (array_slice($plan->signatures, 0, 400) as $signature)
                            <tr>
                                <th scope="row">{{ $signature['signature'] }}</th>
                                <td>{{ collect($signature['topics'])->map(fn ($id) => $topicNames[$id] ?? 'ID '.$id)->join(', ') ?: '–' }}</td>
                                <td><x-ui.badge :variant="$variants[$signature['shelf_status']]">{{ $signature['shelf_status'] === 'new' ? 'Neu' : 'Vorhanden' }}</x-ui.badge></td>
                                <td>{{ $signature['new_links'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="bc-content-section" aria-labelledby="confirm-heading">
        <div class="bc-section-heading"><h2 id="confirm-heading">Import bestätigen</h2></div>

        @if ($plan->errors !== [])
            <p class="bc-section-copy">Beheben die Fehler in den Dateien und lade sie erneut hoch. Bis dahin ist keine Bestätigung möglich.</p>
        @elseif (! $plan->hasChanges() && ($counts['changed_topics'] ?? 0) === 0)
            <p class="bc-section-copy">Der Bestand ist schon auf dem Stand der Dateien. Es gibt nichts zu übernehmen.</p>
        @else
            <p class="bc-section-copy">
                Der Import legt die neuen Einträge in einem Schritt an (alles oder nichts). Medien, Ausleihen, Vormerkungen und Inventarnummern werden nicht verändert,
                vorhandene Regalbretter behalten Reihenfolge und Schalter. Es wird nichts gelöscht.
            </p>
            <form method="post" action="{{ route('administration.classification-import.apply', ['draftId' => $draft->getKey()]) }}" class="bc-calendar-form">
                @csrf
                <input type="hidden" name="fingerprint" value="{{ $plan->fingerprint }}">

                @if (($counts['changed_topics'] ?? 0) > 0)
                    <label class="bc-public-catalog-filter__check">
                        <input type="checkbox" name="update_existing" value="1">
                        <span>
                            <strong>Abweichungen übernehmen</strong>
                            <small>{{ $counts['changed_topics'] }} vorhandene Themen werden an die Datei angepasst (Name, Beschreibung, Schlüssel, Elternthema). Ohne Haken bleiben sie unverändert.</small>
                        </span>
                    </label>
                @endif

                <label class="bc-public-catalog-filter__check">
                    <input type="checkbox" name="confirm" value="1" required>
                    <span><strong>Ja, genau diese geprüften Dateien jetzt importieren.</strong></span>
                </label>

                <x-ui.button type="submit">Import ausführen</x-ui.button>
            </form>
        @endif

        <form method="post" action="{{ route('administration.classification-import.discard', ['draftId' => $draft->getKey()]) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="bc-intake-linkbutton">Entwurf verwerfen</button>
        </form>
    </section>
</x-app-shell>
