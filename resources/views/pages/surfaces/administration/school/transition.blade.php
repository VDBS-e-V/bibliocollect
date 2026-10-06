<x-app-shell surface="administration" title="Schuljahreswechsel">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Schuljahreswechsel"
        lead="Klassen des laufenden Schuljahres den Klassen des neuen Jahres zuordnen. Erst nach Bestätigung wird etwas geändert."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.school.index') }}">← Zurück zu Schule und Schuljahren</a>
    </div>

    @if (session('school_error'))
        <x-ui.alert variant="error" title="Wechsel nicht möglich">{{ session('school_error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if ($from === null)
        <x-ui.alert title="Kein aktives Schuljahr">Es ist noch kein Schuljahr aktiv. Aktiviere zuerst ein Schuljahr unter „Schule und Schuljahre“.</x-ui.alert>
    @elseif ($target === null)
        <x-ui.alert title="Kein Zielschuljahr">Lege unter „Schule und Schuljahre“ das nächste Schuljahr mit seinen Klassen als Entwurf an.</x-ui.alert>
    @else
        <form method="get" action="{{ route('administration.transition.show') }}" class="bc-audit-filter">
            <x-ui.select label="Zielschuljahr" name="target" data-auto-submit>
                @foreach ($candidates as $candidate)
                    <option value="{{ $candidate->getKey() }}" @selected($candidate->is($target))>{{ $candidate->name }}</option>
                @endforeach
            </x-ui.select>
            <noscript><x-ui.button type="submit" variant="secondary">Anzeigen</x-ui.button></noscript>
        </form>

        @php
            $totalPatrons = collect($plan)->sum('patron_count');
            $unmapped = collect($plan)->filter(fn ($row) => $row['patron_count'] > 0 && $row['suggestion'] === '')->count();
        @endphp

        <section class="bc-content-section" aria-labelledby="transition-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="transition-heading">{{ $from->name }} → {{ $target->name }}</h2>
                <span>{{ $totalPatrons }} aktive Ausleihkonten in Klassen</span>
            </div>

            @if ($targetClasses->isEmpty())
                <x-ui.alert title="Keine Zielklassen">Das Zielschuljahr hat noch keine aktiven Klassen.</x-ui.alert>
            @else
                <form method="post" action="{{ route('administration.transition.commit') }}">
                    @csrf
                    <input type="hidden" name="from_id" value="{{ $from->getKey() }}">
                    <input type="hidden" name="target_id" value="{{ $target->getKey() }}">

                    <table class="bc-calendar-table">
                        <thead>
                            <tr>
                                <th scope="col">Klasse</th>
                                <th scope="col">Ausleihkonten</th>
                                <th scope="col">Wohin?</th>
                                <th scope="col">Hinweise</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($plan as $row)
                                @php
                                    $class = $row['class'];
                                    $selected = old('mapping.'.$class->getKey(), $row['suggestion']);
                                @endphp
                                <tr>
                                    <th scope="row">{{ $class->name }} <small class="bc-public-metadata-source">Jahrgang {{ $class->grade_level }}</small></th>
                                    <td class="bc-tabular">{{ $row['patron_count'] }}</td>
                                    <td>
                                        @if ($row['patron_count'] === 0)
                                            <span class="bc-section-copy">keine aktiven Ausleihkonten</span>
                                        @else
                                            <label class="bc-visually-hidden" for="map-{{ $class->getKey() }}">Ziel für {{ $class->name }}</label>
                                            <select id="map-{{ $class->getKey() }}" name="mapping[{{ $class->getKey() }}]" class="bc-field__control">
                                                <option value="" @selected($selected === '')>— bitte wählen —</option>
                                                @foreach ($targetClasses as $targetClass)
                                                    <option value="{{ $targetClass->getKey() }}" @selected($selected === (string) $targetClass->getKey())>{{ $targetClass->name }}</option>
                                                @endforeach
                                                <option value="depart" @selected($selected === 'depart')>Ausscheiden (Abgang)</option>
                                                <option value="keep" @selected($selected === 'keep')>Nicht ändern</option>
                                            </select>
                                        @endif
                                    </td>
                                    <td>
                                        @foreach ($row['blocked'] as $blocked)
                                            <small class="bc-public-metadata-source">{{ $blocked['library_number'] }}: {{ implode(' ', $blocked['reasons']) }}</small>
                                        @endforeach
                                        @if ($row['patron_count'] > 0 && $row['suggestion'] === '')
                                            <small class="bc-public-metadata-source">Keine eindeutige Zielklasse gefunden.</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($unmapped > 0)
                        <x-ui.alert title="Zuordnung offen">{{ $unmapped }} Klasse(n) brauchen noch eine Auswahl.</x-ui.alert>
                    @endif

                    <p class="bc-section-copy">
                        „Ausscheiden“ markiert alle Ausleihkonten der Klasse als ausgeschieden, widerruft offene Aktivierungscodes und deaktiviert verknüpfte Onlinekonten. Das geht nur, wenn nichts mehr ausgeliehen oder vorgemerkt ist. Der Wechsel läuft ganz oder gar nicht und aktiviert danach {{ $target->name }}.
                    </p>

                    <label class="bc-public-catalog-filter__check" for="confirm">
                        <input id="confirm" name="confirm" type="checkbox" value="1">
                        <span><strong>Ich habe die Zuordnung geprüft und möchte {{ $target->name }} jetzt aktivieren.</strong></span>
                    </label>

                    <x-ui.button type="submit">Schuljahreswechsel durchführen</x-ui.button>
                </form>
            @endif
        </section>
    @endif
</x-app-shell>
