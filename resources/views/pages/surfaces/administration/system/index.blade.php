@php
    $labels = ['ok' => 'In Ordnung', 'warn' => 'Achtung', 'fail' => 'Fehler'];
    $variants = ['ok' => 'success', 'warn' => 'warning', 'fail' => 'danger'];
@endphp

<x-app-shell surface="administration" title="Systemzustand">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Systemzustand"
        lead="Läuft alles? Cron, Warteschlange, Sicherung und Fehler auf einen Blick. Fehler werden außerdem per Mail gemeldet, wenn eine Adresse eingetragen ist."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
    </div>

    @if (session('system_success'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('system_success') }}</x-ui.alert>
    @endif

    @if (session('system_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('system_error') }}</x-ui.alert>
    @endif

    <x-ui.alert :variant="$snapshot['status'] === 'ok' ? 'success' : ($snapshot['status'] === 'warn' ? 'warning' : 'error')" title="Gesamtzustand: {{ $labels[$snapshot['status']] }}">
        Stand {{ \Illuminate\Support\Carbon::parse($snapshot['checked_at'])->timezone(config('app.timezone'))->format('d.m.Y H:i') }} Uhr.
    </x-ui.alert>

    <section class="bc-content-section" aria-labelledby="checks-heading">
        <div class="bc-section-heading"><h2 id="checks-heading">Prüfungen</h2></div>
        <table class="bc-calendar-table">
            <thead><tr><th scope="col">Prüfung</th><th scope="col">Ergebnis</th><th scope="col">Einzelheiten</th></tr></thead>
            <tbody>
                @foreach ($snapshot['checks'] as $check)
                    <tr>
                        <th scope="row">{{ $check['label'] }}</th>
                        <td><x-ui.badge :variant="$variants[$check['state']]">{{ $labels[$check['state']] }}</x-ui.badge></td>
                        <td>{{ $check['detail'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="bc-content-section" aria-labelledby="jobs-heading">
        <div class="bc-section-heading"><h2 id="jobs-heading">Zeitplan-Aufgaben</h2></div>
        <p class="bc-section-copy">Diese Aufgaben laufen automatisch über den Cron. Hier kannst du jede einzelne <strong>einmalig sofort</strong> ausführen, zum Beispiel wenn der Cron nicht läuft. Das ändert den Zeitplan nicht. Das Ergebnis erscheint oben, ein Eintrag im Protokoll hält fest, wer es ausgelöst hat.</p>
        <table class="bc-calendar-table">
            <thead><tr><th scope="col">Aufgabe</th><th scope="col">Was sie tut</th><th scope="col">Nächster Lauf</th><th scope="col">Jetzt</th></tr></thead>
            <tbody>
                @foreach ($jobs as $job)
                    <tr>
                        <th scope="row"><code>{{ $job['name'] }}</code></th>
                        <td>{{ $job['description'] }}</td>
                        <td class="bc-tabular">{{ $job['next_run'] }}</td>
                        <td>
                            <form method="post" action="{{ route('administration.system.run-job', ['job' => $job['name']]) }}">
                                @csrf
                                <x-ui.button type="submit" variant="secondary" data-confirm="Die Aufgabe „{{ $job['name'] }}“ jetzt einmal ausführen?" aria-label="Aufgabe {{ $job['name'] }} jetzt einmal ausführen">Einmal ausführen</x-ui.button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <form method="post" action="{{ route('administration.system.run-cron') }}">
            @csrf
            <x-ui.button type="submit" variant="secondary" data-confirm="Einen Cron-Lauf jetzt auslösen (fällige Aufgaben und Warteschlange)?">Cron-Lauf jetzt auslösen</x-ui.button>
        </form>
        <p class="bc-section-copy">Der Cron-Lauf macht dasselbe wie der Cronjob: Er führt die gerade fälligen Aufgaben aus und arbeitet danach etwa 10 Sekunden lang die Warteschlange ab.</p>
    </section>

    <section class="bc-content-section" aria-labelledby="covers-heading">
        <div class="bc-section-heading"><h2 id="covers-heading">Cover der Bücher</h2></div>
        <p class="bc-section-copy">
            <strong>{{ $covers['with'] }}</strong> Ausgaben haben ein Cover, bei <strong>{{ $covers['open'] }}</strong> steht die Suche noch aus, bei <strong>{{ $covers['missing'] }}</strong> wurde schon ergebnislos gesucht. In der Warteschlange warten {{ $covers['waiting'] }} Aufgaben.
            Jede Nacht um 03:30 Uhr reiht der Zeitplan {{ (int) config('catalog.covers.daily_limit', 200) }} Titel ein (einstellbar mit <code>CATALOG_COVER_DAILY_LIMIT</code>); die Cover lädt der Cron im Hintergrund.
        </p>
        <div class="bc-context-actions">
            <form method="post" action="{{ route('administration.system.queue-covers') }}">
                @csrf
                <x-ui.button type="submit" variant="secondary">Cover jetzt suchen (bis zu 200 Titel)</x-ui.button>
            </form>
            <form method="post" action="{{ route('administration.system.queue-covers') }}">
                @csrf
                <input type="hidden" name="retry_missing" value="1">
                <x-ui.button type="submit" variant="secondary" data-confirm="Auch Titel erneut suchen, bei denen schon ergebnislos gesucht wurde? Das lohnt sich zum Beispiel nach dem Eintragen eines Google-Books-Schlüssels." data-confirm-label="Erneut suchen">Auch erfolglos gesuchte erneut suchen</x-ui.button>
            </form>
        </div>
        <p class="bc-section-copy">Nach dem Einreihen „Cron-Lauf jetzt auslösen“ drücken oder den Cron laufen lassen. Mehrere Klicks reihen jeweils die nächsten Titel ein.</p>
    </section>

    <section class="bc-content-section" aria-labelledby="backups-heading">
        <div class="bc-section-heading"><h2 id="backups-heading">Datensicherung</h2></div>
        <p class="bc-section-copy">Die Sicherung läuft täglich automatisch (siehe Zeitplan-Aufgaben). Lade sie regelmäßig herunter und lege sie <strong>außerhalb des Servers</strong> ab: Eine Sicherung nur auf dem Server hilft nicht, wenn der Server ausfällt. Sie enthält alle personenbezogenen Daten und gehört nicht in fremde Hände.</p>
        <form method="post" action="{{ route('administration.system.backup') }}">
            @csrf
            <x-ui.button type="submit" variant="secondary">Jetzt sichern</x-ui.button>
        </form>

        @if ($backups === [])
            <p class="bc-section-copy">Es gibt noch keine Sicherung.</p>
        @else
            <table class="bc-calendar-table">
                <thead><tr><th scope="col">Sicherung</th><th scope="col">Erstellt</th><th scope="col">Größe</th><th scope="col">Herunterladen</th></tr></thead>
                <tbody>
                    @foreach ($backups as $backup)
                        <tr>
                            <th scope="row"><code>{{ $backup['name'] }}</code></th>
                            <td class="bc-tabular">{{ $backup['created'] }}</td>
                            <td class="bc-tabular">{{ number_format($backup['size_kb'], 0, ',', '.') }} KB</td>
                            <td><a href="{{ route('administration.system.backup.download', ['file' => $backup['name']]) }}">Herunterladen</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p class="bc-section-copy">Wiederherstellen: <code>php artisan backup:restore &lt;Datei&gt;</code> oder in phpMyAdmin die <code>.sql.gz</code>-Datei über „Importieren“ einspielen. Beides ersetzt alle Daten. Der Ablauf steht in <code>docs/OPERATIONS.md</code>.</p>
    </section>

    <section class="bc-content-section" aria-labelledby="alerts-heading">
        <div class="bc-section-heading"><h2 id="alerts-heading">Meldungen</h2></div>
        @if ($alertAddress)
            <p class="bc-section-copy">Meldungen über Fehler, fehlgeschlagene Jobs und einen ausgefallenen Cron gehen an <strong>{{ $alertAddress }}</strong>. Dieselbe Meldung wird höchstens alle {{ (int) config('hosting.alert_throttle_minutes', 30) }} Minuten verschickt.</p>
            <form method="post" action="{{ route('administration.system.test-alert') }}">
                @csrf
                <x-ui.button type="submit" variant="secondary">Testmeldung senden</x-ui.button>
            </form>
        @else
            <p class="bc-section-copy">Es ist keine Adresse für Meldungen eingetragen. Setze <code>ALERT_EMAIL</code> in der <code>.env</code>, damit Fehler und ein ausgefallener Cron per Mail gemeldet werden.</p>
        @endif

        @if ($statusUrl)
            <p class="bc-section-copy"><a href="{{ route('administration.mail-preview') }}">Alle E-Mails der Anwendung ansehen (Vorschau)</a></p>
        <p class="bc-section-copy">Für ein Monitoring (zum Beispiel UptimeRobot) gibt es <code>{{ $statusUrl }}</code> mit dem Schlüssel im Header <code>X-Api-Key</code>: Die Antwort ist JSON, bei Fehlern HTTP 503.</p>
        @endif
    </section>

    <section class="bc-content-section" aria-labelledby="errors-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="errors-heading">Letzte Fehler</h2>
            <span>{{ $events->count() }}</span>
        </div>

        @if ($events->isEmpty())
            <p class="bc-section-copy">Keine Fehler festgehalten.</p>
        @else
            <table class="bc-calendar-table">
                <thead>
                    <tr><th scope="col">Zuletzt</th><th scope="col">Art</th><th scope="col">Meldung</th><th scope="col">Stelle</th><th scope="col">Anzahl</th></tr>
                </thead>
                <tbody>
                    @foreach ($events as $error)
                        <tr>
                            <td class="bc-tabular">{{ $error->last_seen_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</td>
                            <td>{{ $error->kind === 'job' ? 'Job' : 'Seite' }}: {{ class_basename($error->class) }}</td>
                            <td>{{ $error->message }}@if ($error->path) <small class="bc-public-metadata-source">{{ $error->method }} {{ $error->path }}</small>@endif</td>
                            <td class="bc-tabular">{{ $error->file }}@if ($error->line):{{ $error->line }}@endif</td>
                            <td class="bc-tabular">{{ $error->occurrences }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="bc-section-copy">Gleiche Fehler sind zusammengefasst. Einträge werden nach 30 Tagen gelöscht. Einzelheiten stehen im Log (<code>storage/logs</code>).</p>
        @endif
    </section>
</x-app-shell>
