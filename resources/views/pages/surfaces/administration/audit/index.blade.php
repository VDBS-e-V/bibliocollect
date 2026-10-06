@php
    $areaLabels = [
        'circulation' => 'Ausleihe',
        'catalog' => 'Katalog',
        'school' => 'Schule',
    ];
@endphp

<x-app-shell surface="administration" title="Protokoll">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Protokoll"
        lead="Nachvollziehbare Ereignisse aus Ausleihe, Katalog und Schule. Das Protokoll enthält Kennungen und Fachwerte, keine Klartextdaten von Personen."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
    </div>

    <form method="get" action="{{ route('administration.audit.index') }}" class="bc-audit-filter">
        <x-ui.select label="Bereich" name="bereich" data-auto-submit>
            <option value="">Alle Bereiche</option>
            @foreach ($areas as $key)
                <option value="{{ $key }}" @selected($area === $key)>{{ $areaLabels[$key] ?? $key }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input label="Suche" name="q" :value="$term" placeholder="Barcode, Kennung oder Text" />
        <x-ui.button type="submit" variant="secondary">Filtern</x-ui.button>
    </form>

    <section class="bc-content-section" aria-labelledby="audit-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="audit-heading">Ereignisse</h2>
            <span>{{ $events->total() }}</span>
        </div>

        @if ($events->isEmpty())
            <p class="bc-section-copy">Keine Ereignisse gefunden.</p>
        @else
            <table class="bc-calendar-table">
                <thead>
                    <tr>
                        <th scope="col">Zeitpunkt</th>
                        <th scope="col">Ereignis</th>
                        <th scope="col">Beschreibung</th>
                        <th scope="col">Durch</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($events as $event)
                        <tr>
                            <th scope="row" class="bc-tabular">{{ $event->occurred_at->setTimezone(config('foundation.business_timezone', 'Europe/Berlin'))->format('d.m.Y H:i') }}</th>
                            <td><code>{{ $event->action }}</code></td>
                            <td>
                                {{ $event->summary }}
                                @if ($event->subject_type)
                                    <small class="bc-public-metadata-source">{{ $event->subject_type }} {{ $event->subject_id }}</small>
                                @endif
                            </td>
                            <td>{{ $event->actor?->name ?? ($event->actor_user_id ? 'Konto '.$event->actor_user_id : 'System') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $events->links() }}
        @endif
    </section>
</x-app-shell>
