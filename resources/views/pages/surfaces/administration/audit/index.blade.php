@php
    $areaLabels = [
        'circulation' => 'Ausleihe',
        'catalog' => 'Katalog',
        'school' => 'Schule',
        'patrons' => 'Ausleihkonten',
        'system' => 'System',
        'wishes' => 'Buchwünsche',
        'audit' => 'Protokoll',
        'privacy' => 'Datenschutz',
    ];
    $filters = array_filter(['bereich' => $area, 'ereignis' => $action, 'q' => $term, 'von' => $from, 'bis' => $to, 'von_konto' => $actorId, 'person' => $person]);
@endphp

<x-app-shell surface="administration" title="Protokoll">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Protokoll"
        lead="Nachvollziehbare Ereignisse aus Ausleihe, Katalog, Schule und System. Das Protokoll selbst speichert nur Kennungen und Fachwerte; Namen und Nummern von Personen werden zur Anzeige aus den Ausleihkonten aufgelöst."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
        @if ($filters !== [])
            <a href="{{ route('administration.audit.index') }}">Filter zurücksetzen</a>
        @endif
    </div>

    <form method="get" action="{{ route('administration.audit.index') }}" class="bc-audit-filter bc-audit-filter--wide" role="search">
        <x-ui.select label="Bereich" name="bereich" id="audit-area">
            <option value="">Alle Bereiche</option>
            @foreach ($areas as $key)
                <option value="{{ $key }}" @selected($area === $key)>{{ $areaLabels[$key] ?? $key }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select label="Ereignis" name="ereignis" id="audit-action">
            <option value="">Alle Ereignisse</option>
            @foreach ($actions as $key)
                <option value="{{ $key }}" @selected($action === $key)>{{ $key }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input label="Person (Name oder Bibliotheksnummer)" name="person" id="audit-person" :value="$person" placeholder="zum Beispiel Mia Müller oder 123456" />
        <x-ui.select label="Durchgeführt von (Konto)" name="von_konto" id="audit-actor">
            <option value="">Alle Konten</option>
            @foreach ($actors as $id => $name)
                <option value="{{ $id }}" @selected((int) $actorId === (int) $id)>{{ $name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input label="Von (Datum)" name="von" id="audit-from" type="date" :value="$from" />
        <x-ui.input label="Bis (Datum)" name="bis" id="audit-to" type="date" :value="$to" />
        <x-ui.input label="Suche im Text" name="q" id="audit-q" :value="$term" placeholder="Barcode, Kennung oder Text" />
        <x-ui.button type="submit" variant="secondary">Filtern</x-ui.button>
    </form>

    @if ($personFound !== null)
        <p class="bc-section-copy">
            @if ($personFound === 0)
                <strong>Keine Person gefunden</strong> zu „{{ $person }}“. Gesucht wird in Vor- und Nachname und in der Bibliotheksnummer.
            @else
                Ereignisse zu <strong>{{ $personFound }}</strong> {{ $personFound === 1 ? 'Person' : 'Personen' }}, die zu „{{ $person }}“ passen{{ $personFound >= 100 ? ' (höchstens 100 werden berücksichtigt)' : '' }}.
            @endif
        </p>
    @endif

    <section class="bc-content-section" aria-labelledby="audit-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="audit-heading">Ereignisse</h2>
            <span>{{ $events->total() }}</span>
        </div>

        @if ($events->isEmpty())
            <p class="bc-section-copy">Keine Ereignisse gefunden.</p>
        @else
            <p class="bc-section-copy">
                <a href="{{ route('administration.audit.export', $filters) }}">Diese Auswahl als CSV exportieren</a>
                (bis zu 20.000 Einträge; der Export wird selbst protokolliert).
            </p>
            <table class="bc-calendar-table">
                <thead>
                    <tr>
                        <th scope="col">Zeitpunkt</th>
                        <th scope="col">Ereignis</th>
                        <th scope="col">Beschreibung</th>
                        <th scope="col">Person</th>
                        <th scope="col">Durch</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($events as $event)
                        @php
                            $patronId = isset($patrons[(string) $event->subject_id]) ? (string) $event->subject_id : (string) ($event->context['patron_id'] ?? '');
                        @endphp
                        <tr>
                            <th scope="row" class="bc-tabular">{{ $event->occurred_at->setTimezone(config('foundation.business_timezone', 'Europe/Berlin'))->format('d.m.Y H:i') }}</th>
                            <td><code>{{ $event->action }}</code></td>
                            <td>
                                {{ $event->summary }}
                                @if ($event->subject_type)
                                    <small class="bc-public-metadata-source">{{ class_basename($event->subject_type) }} {{ $event->subject_id }}</small>
                                @endif
                            </td>
                            <td>{{ $patrons[$patronId] ?? '—' }}</td>
                            <td>{{ $event->actor?->name ?? ($event->actor_user_id ? 'Konto '.$event->actor_user_id : 'System') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $events->links() }}
        @endif
    </section>
</x-app-shell>
