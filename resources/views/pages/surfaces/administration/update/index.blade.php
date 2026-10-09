<x-app-shell surface="administration" title="Update">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Update"
        lead="Ein neues Programmpaket einspielen, ohne Konsole. Die Seite wird dabei kurz in den Wartungsmodus gesetzt, vorher sichert das System die Datenbank."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
        <a href="{{ route('administration.system.index') }}">Systemzustand</a>
    </div>

    @if (session('update_success'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('update_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Nicht möglich">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if ($pending)
        <x-ui.alert variant="warning" title="Ein Update wartet auf den Abschluss">Das Paket „{{ $pending['package'] }}“ ist eingespielt. Der Abschluss (Datenbank aktualisieren, Wartungsmodus beenden) läuft beim nächsten Cron-Aufruf, spätestens nach wenigen Minuten. <a href="{{ route('update.finish', ['token' => $pending['token']]) }}">Jetzt abschließen</a></x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="update-state-heading">
        <div class="bc-section-heading"><h2 id="update-state-heading">So läuft es gerade</h2></div>
        <dl class="bc-detail-list">
            <div><dt>Installierte Version</dt><dd><strong>{{ $current }}</strong></dd></div>
            <div><dt>PHP</dt><dd>{{ $php }}</dd></div>
            <div><dt>Wartungsmodus</dt><dd>{{ $maintenance ? 'An: Die Seite ist für Besucher gesperrt.' : 'Aus' }}</dd></div>
        </dl>
    </section>

    <section class="bc-content-section" aria-labelledby="update-new-heading">
        <div class="bc-section-heading"><h2 id="update-new-heading">1. Neues Paket bereitstellen</h2></div>
        <p class="bc-section-copy">Das Paket ist die ZIP-Datei aus <code>build-release.ps1</code>. Du kannst sie hier hochladen (Grenze des Servers: <strong>{{ $limit }}</strong>) oder per FTP in den Ordner <code>{{ $directory }}</code> legen, dann erscheint sie unten.</p>
        <form method="post" action="{{ route('administration.update.upload') }}" enctype="multipart/form-data" class="bc-calendar-form">
            @csrf
            <x-ui.input label="Paket (ZIP)" name="package" type="file" accept=".zip" />
            <x-ui.button type="submit" variant="secondary">Paket hochladen und prüfen</x-ui.button>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="update-packages-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="update-packages-heading">2. Bereitliegende Pakete</h2>
            <span>{{ count($packages) }}</span>
        </div>

        @forelse ($packages as $package)
            <article class="bc-update-package">
                <div class="bc-update-package__main">
                    <strong>{{ $package['name'] }}</strong>
                    <span>Version <strong>{{ $package['version'] ?? 'unbekannt' }}</strong> · {{ number_format($package['size'] / 1048576, 1, ',', '.') }} MB · {{ \Illuminate\Support\Carbon::createFromTimestamp($package['modified'])->timezone(config('foundation.business_timezone', 'Europe/Berlin'))->format('d.m.Y H:i') }}</span>
                    @if ($package['error'])
                        <x-ui.badge variant="danger">nicht verwendbar</x-ui.badge> <span>{{ $package['error'] }}</span>
                    @elseif ($package['newer'] === true)
                        <x-ui.badge variant="success">neuer als installiert</x-ui.badge>
                    @elseif ($package['newer'] === false)
                        <x-ui.badge variant="warning">nicht neuer</x-ui.badge>
                    @else
                        <x-ui.badge>Version nicht vergleichbar</x-ui.badge>
                    @endif
                </div>
                <div class="bc-update-package__actions">
                    @if (! $package['error'] && ! $pending)
                        <form method="post" action="{{ route('administration.update.apply') }}">
                            @csrf
                            <input type="hidden" name="package" value="{{ $package['name'] }}">
                            <label class="bc-checkbox-line"><input type="checkbox" name="confirm" value="1"> Ich weiß: Die Seite ist dabei kurz gesperrt, die Datenbank wird vorher gesichert.</label>
                            @if ($package['newer'] !== true)
                                <label class="bc-checkbox-line"><input type="checkbox" name="allow_older" value="1"> Auch einspielen, wenn das Paket nicht neuer ist.</label>
                            @endif
                            <x-ui.button type="submit" data-confirm="Das Update jetzt einspielen? Die Seite ist dabei für kurze Zeit nicht erreichbar." data-confirm-label="Jetzt einspielen">Jetzt einspielen</x-ui.button>
                        </form>
                    @endif
                    <form method="post" action="{{ route('administration.update.destroy', ['name' => $package['name']]) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="bc-intake-linkbutton" data-confirm="Das Paket „{{ $package['name'] }}“ löschen?" data-confirm-label="Löschen">Paket löschen</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="bc-section-copy">Es liegt kein Paket bereit.</p>
        @endforelse
    </section>

    <section class="bc-content-section" aria-labelledby="update-auto-heading">
        <div class="bc-section-heading"><h2 id="update-auto-heading">3. Automatisch nachts einspielen</h2></div>
        <p class="bc-section-copy">Ist das eingeschaltet, spielt das System ein bereitliegendes, <strong>neueres</strong> Paket nachts um <strong>03:15 Uhr</strong> ein. Dafür muss der Cron laufen. Der Abschluss folgt beim nächsten Cron-Aufruf.</p>
        <form method="post" action="{{ route('administration.update.auto') }}">
            @csrf
            <label class="bc-checkbox-line"><input type="hidden" name="auto" value="0"><input type="checkbox" name="auto" value="1" @checked($auto)> Nachts automatisch einspielen</label>
            <x-ui.button type="submit" variant="secondary">Speichern</x-ui.button>
        </form>
    </section>

    @if ($last)
        <section class="bc-content-section" aria-labelledby="update-last-heading">
            <div class="bc-section-heading"><h2 id="update-last-heading">Letztes Update</h2></div>
            <x-ui.alert :variant="$last['ok'] ? 'success' : 'error'" :title="$last['ok'] ? 'Erfolgreich' : 'Fehlgeschlagen'">
                {{ $last['message'] }}
                @if (! empty($last['steps']))
                    <ul>@foreach ($last['steps'] as $step)<li>{{ $step }}</li>@endforeach</ul>
                @endif
                <small>{{ \Illuminate\Support\Carbon::parse($last['at'])->timezone(config('foundation.business_timezone', 'Europe/Berlin'))->format('d.m.Y H:i') }} Uhr</small>
            </x-ui.alert>
        </section>
    @endif

    <section class="bc-content-section" aria-labelledby="update-how-heading">
        <div class="bc-section-heading"><h2 id="update-how-heading">Was beim Einspielen passiert</h2></div>
        <ol class="bc-section-copy">
            <li>Die Datenbank wird gesichert (Systemzustand → Datensicherung). Schlägt das fehl, bleibt alles wie es ist.</li>
            <li>Die Seite geht in den <strong>Wartungsmodus</strong>.</li>
            <li>Das Paket wird entpackt und über die Anwendung kopiert. <strong>Unberührt</strong> bleiben die Einstellungen (.env), Cover, Ausweis-Motive und alles unter storage.</li>
            <li>Der <strong>Abschluss</strong> läuft mit dem neuen Code: Datenbank aktualisieren, Zwischenspeicher leeren, Wartungsmodus beenden.</li>
        </ol>
        <p class="bc-section-copy">Geht etwas schief, steht der Grund hier unter „Letztes Update“. Dann hilft die Sicherung (phpMyAdmin) und, falls die Seite im Wartungsmodus hängt, das Löschen der Datei <code>storage/framework/down</code> per FTP.</p>
    </section>
</x-app-shell>
