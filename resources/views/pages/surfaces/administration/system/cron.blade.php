<x-app-shell surface="administration" title="Cron und Aufgaben">
    <x-ui.page-header
        kicker="Systemzustand"
        title="Cron und Aufgaben"
        lead="Was automatisch läuft, wann es zuletzt und als Nächstes dran ist, und was du von Hand anstoßen kannst, ohne die Konsole zu brauchen."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.system.index') }}">← Zurück zum Systemzustand</a>
    </div>

    @if (session('system_success'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('system_success') }}</x-ui.alert>
    @endif

    @if (session('system_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('system_error') }}</x-ui.alert>
    @endif

    @if (session('task_output'))
        <section class="bc-content-section" aria-labelledby="output-heading">
            <h2 id="output-heading">Ergebnis: {{ session('task_label') }}</h2>
            <pre class="bc-task-output" tabindex="0">{{ session('task_output') }}</pre>
        </section>
    @endif

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

    <section class="bc-content-section" aria-labelledby="manual-heading">
        <div class="bc-section-heading"><h2 id="manual-heading">Weitere Aufgaben (nicht im Zeitplan)</h2></div>
        <p class="bc-section-copy">Diese Aufgaben laufen nur auf Knopfdruck. Das Ergebnis erscheint oben auf der Seite, ein Eintrag im Protokoll hält fest, wer sie ausgelöst hat.</p>
        <table class="bc-calendar-table">
            <thead><tr><th scope="col">Aufgabe</th><th scope="col">Was sie tut</th><th scope="col">Jetzt</th></tr></thead>
            <tbody>
                @foreach ($tasks as $key => $task)
                    <tr>
                        <th scope="row">{{ $task['label'] }}</th>
                        <td>{{ $task['description'] }}</td>
                        <td>
                            <form method="post" action="{{ route('administration.system.run-task', ['task' => $key]) }}">
                                @csrf
                                <x-ui.button type="submit" variant="secondary" aria-label="{{ $task['label'] }} jetzt ausführen">Ausführen</x-ui.button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="bc-content-section" aria-labelledby="setup-heading">
        <div class="bc-section-heading"><h2 id="setup-heading">Cronjob einrichten</h2></div>
        <p class="bc-section-copy">
            Damit der Zeitplan läuft, muss beim Hoster ein Cronjob jede Minute (oder mindestens alle fünf Minuten) die Cron-Adresse der Anwendung aufrufen.
            @if ($statusUrl)
                Der Zustand ist unter <code>{{ $statusUrl }}</code> abrufbar (mit Token).
            @endif
            Die genaue Einrichtung steht in der Dokumentation (<code>docs/HOSTING_SHARED.md</code>). Ein Cron-Lauf von Hand hilft, bis er läuft.
        </p>
    </section>
</x-app-shell>
