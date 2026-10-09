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

    <section class="bc-content-section" aria-labelledby="install-heading">
        <div class="bc-section-heading"><h2 id="install-heading">Installation</h2></div>
        <table class="bc-calendar-table">
            <thead><tr><th scope="col">Was</th><th scope="col">Stand</th><th scope="col">Ergebnis</th></tr></thead>
            <tbody>
                @foreach ($installation as $row)
                    <tr>
                        <th scope="row">{{ $row['label'] }}</th>
                        <td>{{ $row['value'] }}@if ($row['hint'])<br><small class="bc-public-metadata-source">{{ $row['hint'] }}</small>@endif</td>
                        <td><x-ui.badge :variant="['ok' => 'success', 'warn' => 'warning', 'fail' => 'danger'][$row['state']]">{{ ['ok' => 'In Ordnung', 'warn' => 'Hinweis', 'fail' => 'Fehler'][$row['state']] }}</x-ui.badge></td>
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

    <section class="bc-content-section bc-covers" aria-labelledby="covers-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="covers-heading">Cover der Bücher</h2>
            <span>{{ $covers['percent'] }} %</span>
        </div>

        <p class="bc-covers__lead"><strong>{{ $covers['with'] }}</strong> von {{ $covers['total'] }} Ausgaben haben ein Cover.</p>
        <div class="bc-meter bc-meter--wide" role="img" aria-label="{{ $covers['with'] }} von {{ $covers['total'] }} Ausgaben mit Cover"><span class="bc-meter__bar" style="width: {{ $covers['percent'] }}%"></span></div>

        <dl class="bc-covers__stats">
            <div><dt>Mit Cover</dt><dd>{{ $covers['with'] }}</dd></div>
            <div><dt>Suche steht aus</dt><dd>{{ $covers['open'] }}</dd></div>
            <div><dt>Erfolglos gesucht</dt><dd>{{ $covers['missing'] }}</dd></div>
            <div><dt>Fehler beim Laden</dt><dd>{{ $covers['failed'] }}</dd></div>
            <div><dt>Ohne ISBN (nicht suchbar)</dt><dd>{{ $covers['noIdentifier'] }}</dd></div>
        </dl>

        <ul class="bc-covers__facts">
            <li><strong>Nächtlicher Lauf:</strong> um 03:30 Uhr werden {{ $covers['perNight'] }} Titel eingereiht (<code>CATALOG_COVER_DAILY_LIMIT</code>).@if ($covers['open'] > 0) Bei diesem Tempo dauert es noch etwa <strong>{{ max(1, $covers['nights']) }} {{ max(1, $covers['nights']) === 1 ? 'Nacht' : 'Nächte' }}</strong>, bis alle offenen Titel versucht wurden.@endif</li>
            <li><strong>Warteschlange:</strong> {{ $covers['queued'] }} Cover-Aufgaben warten@if ($covers['failedJobs'] > 0), {{ $covers['failedJobs'] }} sind fehlgeschlagen@endif. Der Cron arbeitet sie ab.</li>
            <li><strong>Quellen:</strong> Open Library <x-ui.badge :variant="$covers['openLibrary'] ? 'success' : 'neutral'">{{ $covers['openLibrary'] ? 'an' : 'aus' }}</x-ui.badge> · Google Books <x-ui.badge :variant="$covers['google'] ? 'success' : 'warning'">{{ $covers['google'] ? 'Schlüssel eingetragen' : 'ohne Schlüssel (aus)' }}</x-ui.badge></li>
        </ul>

        <div class="bc-context-actions">
            <form method="post" action="{{ route('administration.system.queue-covers') }}">
                @csrf
                <x-ui.button type="submit" variant="secondary">Cover jetzt suchen (bis zu 200 Titel)</x-ui.button>
            </form>
            <form method="post" action="{{ route('administration.system.queue-covers') }}">
                @csrf
                <input type="hidden" name="retry_missing" value="1">
                <x-ui.button type="submit" variant="secondary" data-confirm="Auch Titel erneut suchen, bei denen schon ergebnislos gesucht wurde? Das lohnt sich zum Beispiel nach dem Eintragen eines Google-Books-Schlüssels." data-confirm-label="Erneut suchen">Auch erfolglos gesuchte erneut suchen ({{ $covers['missing'] }})</x-ui.button>
            </form>
        </div>
        <p class="bc-section-copy">Nach dem Einreihen „Cron-Lauf jetzt auslösen“ drücken oder den Cron laufen lassen. Mehrere Klicks reihen jeweils die nächsten Titel ein.</p>

        @if ($covers['recent']->isNotEmpty())
            <h3 class="bc-covers__recent-heading">Zuletzt geholt</h3>
            <ul class="bc-covers__recent">
                @foreach ($covers['recent'] as $edition)
                    <li>
                        <img src="{{ app(\App\Modules\Catalog\Services\CatalogCoverService::class)->localUrlForEdition($edition) }}" alt="" loading="lazy" width="64" height="90">
                        <span>{{ \Illuminate\Support\Str::limit($edition->title->preferred_title, 40) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="bc-content-section" aria-labelledby="quality-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="quality-heading">Datenqualität</h2>
            <span>{{ $quality['open_defects'] }} offene Mängel</span>
        </div>
        <p class="bc-section-copy">
            Der Cron holt nachts Vorschläge (DNB, bei fehlender Zusammenfassung auch Google Books oder Open Library) für offene Fälle der Katalogqualität. Der Katalog selbst ändert sich dabei nie; übernommen wird in der <a href="{{ route('pos.catalog.quality.index') }}">Katalogqualität</a>.
            Noch ohne Vorschlag: <strong>{{ $quality['waiting_defects'] }}</strong> Mängel{!! $quality['waiting_enrichment'] > 0 ? ' und <strong>'.e($quality['waiting_enrichment']).'</strong> Fälle nur zur Anreicherung' : '' !!}.
            {!! $quality['nights'] > 0 ? 'Bei der gewählten Menge dauert das etwa <strong>'.e($quality['nights']).'</strong> '.($quality['nights'] === 1 ? 'Nacht' : 'Nächte').'.' : '' !!}
        </p>

        <form method="post" action="{{ route('administration.system.quality') }}" class="bc-quality-queue">
            @csrf
            <table class="bc-calendar-table bc-stack-table">
                <thead>
                    <tr>
                        <th scope="col">Zuerst nachts holen</th>
                        <th scope="col">Problem</th>
                        <th scope="col">Offen</th>
                        <th scope="col">Noch ohne Vorschlag</th>
                        <th scope="col">Vorschlag liegt vor</th>
                        <th scope="col">Ohne Treffer</th>
                        <th scope="col">Quelle nicht erreichbar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quality['rows'] as $row)
                        <tr>
                            <td><label class="bc-checkbox-line"><input type="checkbox" name="issues[]" value="{{ $row['issue']->value }}" @checked(in_array($row['issue']->value, $quality['settings']['issues'], true))> <span class="bc-visually-hidden">{{ $row['issue']->label() }} zuerst holen</span></label></td>
                            <th scope="row">{{ $row['issue']->label() }}@if ($row['issue']->isEnrichment()) <x-ui.badge variant="neutral">Anreicherung</x-ui.badge>@endif</th>
                            <td class="bc-tabular" data-label="Offen">{{ $row['open'] }}</td>
                            <td class="bc-tabular" data-label="Noch ohne Vorschlag">{{ $row['waiting'] }}</td>
                            <td class="bc-tabular" data-label="Vorschlag liegt vor">{{ $row['ready'] }}</td>
                            <td class="bc-tabular" data-label="Ohne Treffer">{{ $row['none'] }}</td>
                            <td class="bc-tabular" data-label="Quelle nicht erreichbar">{{ $row['unavailable'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <label class="bc-checkbox-line"><input type="checkbox" name="enrichment" value="1" @checked($quality['settings']['enrichment'])> Auch Fälle nur zur Anreicherung (Zusammenfassung, Schlagwörter) nachts bearbeiten</label>
            <x-ui.input label="Fälle pro Nacht" name="per_night" id="quality-per-night" type="number" min="1" max="500" :value="old('per_night', $quality['settings']['per_night'])" hint="Der Lauf um 02:30 Uhr arbeitet erst die angehakten Problemarten ab (schwerste zuerst), dann den Rest. Kleine Mengen passen auch als Web-Cron in die Laufzeit-Grenze des Anbieters." />
            <div class="bc-context-actions">
                <x-ui.button type="submit">Auswahl speichern</x-ui.button>
            </div>
        </form>

        <form method="post" action="{{ route('administration.system.run-job', ['job' => 'catalog:quality:propose']) }}">
            @csrf
            <x-ui.button type="submit" variant="secondary" data-confirm="Jetzt einen Lauf mit der gespeicherten Auswahl starten? Er fragt externe Quellen ab und kann einige Minuten dauern.">Jetzt ein Stück abarbeiten</x-ui.button>
        </form>
        <p class="bc-section-copy">„Jetzt ein Stück abarbeiten“ verwendet die gespeicherte Auswahl, nicht die Haken, die du eben erst gesetzt hast. Erst speichern.</p>
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
