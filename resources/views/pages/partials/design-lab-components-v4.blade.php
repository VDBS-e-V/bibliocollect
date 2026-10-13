{{-- K2b/K4b: Interaktive, universelle Komponenten. Keine Schreibaktionen oder realen Fachzustände. --}}
<section class="bc-core-gallery__section" id="bc-core-overlays" aria-labelledby="bc-core-overlays-heading">
    <x-ui.section-heading eyebrow="14 · Navigation und Kontext" title="Aktionsmenü, Popover und Tooltip" description="Drei unterschiedliche Werkzeuge: weitere Links, ergänzende Inhalte und sehr kurze Hilfe. Die Menüs funktionieren mit Tastatur und schließen bei Escape oder einem Klick außerhalb." />
    <div class="bc-core-gallery-v4__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head">
                <strong id="bc-core-overlays-heading">Aktionsmenü</strong>
                <span>x-ui.action-menu</span>
            </div>
            <x-ui.action-menu label="Mehr anzeigen" :items="[
                ['label' => 'Zu den Formularen', 'href' => '#bc-core-forms'],
                ['label' => 'Zu den Datentabellen', 'href' => '#bc-core-details'],
                ['divider' => true],
                ['label' => 'Nicht verfügbar', 'disabled' => true],
            ]" />
            <p class="bc-core-gallery-v4__explain">Eine Liste echter Links. Gesperrte Einträge sind nicht versehentlich anklickbar. Keine nachgebildete ARIA-Anwendungsmenüsteuerung.</p>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head">
                <strong>Popover mit Inhalt</strong>
                <span>x-ui.popover</span>
            </div>
            <x-ui.popover label="Was bedeutet verfügbar?">
                <h3>Verfügbarkeit eines Mediums</h3>
                <p>Die Verfügbarkeit hängt von Exemplaren und dem aktuellen Ausleihstatus ab. Hier zeigen wir nur einen erklärenden Demo-Text.</p>
                <a href="#bc-core-feedback">Zu den Statusanzeigen →</a>
            </x-ui.popover>
            <p class="bc-core-gallery-v4__explain">Für kleinere Erläuterungen mit mehreren Sätzen oder einem weiterführenden Link.</p>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head">
                <strong>Tooltip bei Fokus oder Hover</strong>
                <span>x-ui.tooltip</span>
            </div>
            <x-ui.tooltip label="ISBN" text="Die ISBN identifiziert eine bestimmte Buchausgabe. Sie enthält keine Aussage über den Ausleihstatus." />
            <p class="bc-core-gallery-v4__explain">Kurze Hilfe direkt am Begriff. Größere Informationen gehören in ein Popover oder neben das Eingabefeld.</p>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head">
                <strong>Compact-Menü</strong>
                <span>Alternative Dichte</span>
            </div>
            <x-ui.action-menu label="Weitere Optionen" density="compact" align="end" :items="[
                ['label' => 'Formularbeispiele', 'href' => '#bc-core-inputs-advanced'],
                ['label' => 'Listenbeispiele', 'href' => '#bc-core-list-rows'],
                ['label' => 'Löschen (in dieser Demo gesperrt)', 'disabled' => true],
            ]" />
            <p class="bc-core-gallery-v4__explain">Kleinere Innenabstände, aber weiterhin ausreichend große Bedienflächen.</p>
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.action-menu&gt; · &lt;x-ui.popover&gt; · &lt;x-ui.tooltip&gt;</code><span>Blade-API mit Daten, Slots und vorhandenen Markentokens; keine Fachaktionen.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-dialogs" aria-labelledby="bc-core-dialogs-heading">
    <x-ui.section-heading eyebrow="15 · Dialoge" title="Inhalte in echten Dialogen" description="Native Dialog-Elemente für fokussierte Aufgaben. Die bestehende produktive Bestätigungslogik bleibt unverändert; hier prüfen wir nur die universelle Präsentation." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head">
            <strong id="bc-core-dialogs-heading">Dialoge testen</strong>
            <span>x-ui.dialog</span>
        </div>
        <div class="bc-core-gallery__button-row">
            <button class="bc-button bc-button--primary" type="button" data-ui-dialog-open="bc-ui-demo-information">Informationsdialog öffnen</button>
            <button class="bc-button bc-button--secondary" type="button" data-ui-dialog-open="bc-ui-demo-warning">Bestätigungsvorschau öffnen</button>
        </div>
        <p class="bc-core-gallery-v4__explain">Neues Grunddesign: 10 px Eckenradius, feiner Rahmen und bei Warnungen nur eine kurze Akzentleiste im Header – keine durchgehende obere Linie. Schließe den Dialog über X, „Schließen“ oder Escape; der Fokus kehrt zum auslösenden Button zurück.</p>
    </div>

    <x-ui.dialog id="bc-ui-demo-information" title="Informationen zum Medium" description="Eine reine Demonstration des gemeinsamen Dialog-Grundbausteins.">
        <x-ui.description-list density="compact" :items="[
            ['label' => 'Titel', 'value' => 'Zwischen den Zeilen'],
            ['label' => 'Medientyp', 'value' => 'Buch'],
            ['label' => 'Standort', 'value' => 'Regal B'],
        ]" />
    </x-ui.dialog>

    <x-ui.dialog id="bc-ui-demo-warning" title="Aktion bestätigen?" description="In der Designstudie wird nichts gelöscht, gespeichert oder verschickt." tone="warning">
        <p>Hier prüfen wir Größe, Textabstände, die Reihenfolge der Aktionen und die Bedienung per Tastatur. Eine echte kritische Aktion wird erst später angebunden.</p>
        <x-slot:actions>
            <button class="bc-button bc-button--secondary" type="button" data-ui-dialog-close>Abbrechen</button>
            <button class="bc-button bc-button--danger" type="button" disabled>Aktion gesperrt (Demo)</button>
        </x-slot:actions>
    </x-ui.dialog>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.dialog&gt;</code><span>id, title, description, tone, Actions-Slot; natives showModal, Escape und fokussierter Rücksprung.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-notifications" aria-labelledby="bc-core-notifications-heading">
    <x-ui.section-heading eyebrow="16 · Benachrichtigungen" title="Rückmeldungen, die nicht im Weg stehen" description="Nicht jede Information braucht einen blockierenden Dialog. Kurze Nachrichten bleiben nachvollziehbar und auf Wunsch schließbar." />
    <div class="bc-core-gallery-v4__grid">
        <div class="bc-core-gallery-v4__stack">
            <x-ui.toast title="Hinweis" message="Eine neutrale Statusinformation aus dem Designlab." variant="info" />
            <x-ui.toast title="Beispiel erfolgreich abgeschlossen" message="Nur eine Demonstration – es wurden keine Daten geändert." variant="success" />
        </div>
        <div class="bc-core-gallery-v4__stack">
            <x-ui.toast title="Bitte prüfen" message="Ein Vorgang benötigt zusätzliche Aufmerksamkeit." variant="warning" />
            <x-ui.toast title="Beispiel eines Fehlers" message="Die Anwendung würde hier erklären, was fehlgeschlagen ist." variant="error" />
        </div>
    </div>
    <p class="bc-core-gallery-v4__explain">Neues Grunddesign: 8 px Eckenradius, dezente Statustönung und ein kurzer Akzent oberhalb des Titels statt langer farbiger Seitenlinien. Die Meldungen sind Demo-Zustände, lassen sich schließen und vermitteln ihre Bedeutung zusätzlich durch den Text.</p>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.toast&gt;</code><span>info, success, warning, error; 8-px-Radius, kurze Akzentleiste, dezente Flächentönung, role=status oder role=alert; ohne automatisches Ausblenden.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-loading">
    <x-ui.section-heading eyebrow="17 · Ladezustände" title="Laden ohne erfundene Prozentwerte" description="Wenn keine Gesamtzahl bekannt ist, verwenden wir einen Spinner mit lesbarem Status statt eines unzutreffenden Fortschrittsbalkens." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head">
            <strong>Spinner-Größen</strong>
            <span>x-ui.spinner</span>
        </div>
        <div class="bc-core-gallery-v4__loading">
            <x-ui.spinner size="sm" label="Kleine Anzeige · Demo" />
            <x-ui.spinner label="Medien werden geladen · Demo" />
            <x-ui.spinner size="lg" label="Große Anzeige · Demo" />
        </div>
        <p class="bc-core-gallery-v4__explain">Die Drehbewegung wird bei reduzierter Bewegung deaktiviert. Alle Statusangaben sind als Beispiel markiert.</p>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.spinner&gt;</code><span>sm, md, lg und Textlabel; kein falscher Prozentfortschritt und keine echte Netzwerkanfrage.</span></div>
</section>
