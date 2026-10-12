{{-- K11: real reusable components only; links are in-page anchors to review areas, never backend writes. --}}
<section class="bc-core-gallery__section" id="bc-core-result-assistance" aria-labelledby="bc-core-result-assistance-heading">
    <x-ui.section-heading eyebrow="48 · Ergebnishilfe" title="Komplexe Such- und Fehlerzustände verständlich auflösen" description="Der neue Baustein kombiniert vorhandene Empty-State- und Alert-Komponenten mit sinnvollen Alternativen. Er erzeugt weder Treffer noch eine falsche Wiederholungsaktion." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-result-assistance-heading">Keine Treffer · Comfortable</strong><span>x-ui.result-assistance</span></div>
            <x-ui.result-assistance title="Für diese Suche gibt es keine Treffer" description="Andere Schreibweisen oder weniger Filter können helfen." query="Die außergewöhnlich lange Geschichte der unauffindbaren Handschriften" :suggestions="[
                ['label' => 'Suchfeld ansehen', 'href' => '#bc-core-navigation'],
                ['label' => 'Filteroptionen ansehen', 'href' => '#bc-core-filter-panel'],
                ['label' => 'Noch nicht verfügbar', 'disabled' => true],
            ]">
                <x-slot:actions><a href="#bc-core-results-overview" class="bc-button bc-button--secondary bc-button--sm">Ergebnisübersicht ansehen</a></x-slot:actions>
            </x-ui.result-assistance>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Fehler und eingeschränkte Daten · Compact</strong><span>error / unavailable</span></div>
            <div class="bc-core-gallery-v11__stack">
                <x-ui.result-assistance state="error" density="compact" title="Ergebnisse konnten nicht geladen werden" description="Die Vorschau zeigt nur die Fehlerdarstellung. Es wird keine Abfrage gestartet.">
                    <x-slot:actions><a href="#bc-core-notifications" class="bc-button bc-button--tertiary bc-button--sm">Fehlermeldungen vergleichen</a></x-slot:actions>
                </x-ui.result-assistance>
                <x-ui.result-assistance state="unavailable" density="compact" title="Dieser Datenbereich ist nicht verfügbar" description="Eine spätere Fachanwendung entscheidet, ob Berechtigungen oder Daten fehlen." />
            </div>
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.result-assistance&gt;</code><span>state: empty/error/unavailable · query, title, description, suggestions[], actions-Slot, density. Verwendet bestehende Empty-State-/Alert-Komponenten.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-section-index" aria-labelledby="bc-core-section-index-heading">
    <x-ui.section-heading eyebrow="49 · Seitennavigation" title="Lange Detailseiten ohne verschachtelte Tab-Reiter erschließen" description="Eine echte Ankernavigation für Kapitel und Datensatzabschnitte. Linkfokus und aktuelle Fragmentposition werden zugänglich angezeigt." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-section-index-heading">Comfortable · Abschnittsanker</strong><span>x-ui.section-index</span></div>
            <x-ui.section-index title="Abschnitte im Designsystem" current="#bc-core-section-index" :items="[
                ['label' => 'Ergebnishilfe und alternative Suchwege', 'href' => '#bc-core-result-assistance'],
                ['label' => 'Abschnittsübersicht', 'href' => '#bc-core-section-index'],
                ['label' => 'Bearbeitungsstand', 'href' => '#bc-core-review-overview'],
                ['label' => 'Änderungsvergleich', 'href' => '#bc-core-change-review'],
                ['label' => 'Nicht vorhandener Bereich', 'disabled' => true],
            ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · ohne Daten</strong><span>density=compact</span></div>
            <x-ui.section-index title="Kapitel ohne Abschnitte" density="compact" :items="[]" />
            <p class="bc-core-gallery-v11__note">Die aktiven Links führen zu echten Ankern auf dieser Seite. Leere und deaktivierte Einträge sind keine Pseudolinks.</p>
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.section-index&gt;</code><span>title, label, current, items[] mit href/disabled, density · native Links und aktuelle Position per Fragmentwechsel. Kein zweites Tabs-Pattern.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-review-overview" aria-labelledby="bc-core-review-overview-heading">
    <x-ui.section-heading eyebrow="50 · Bearbeitungsübersicht" title="Mehrere Prüfbereiche ohne starren Schrittzwang überblicken" description="Nichtlinearer Statusüberblick: Bereiche können unabhängig offen, in Arbeit, geprüft oder fehlerhaft sein. Der vorhandene Process-Stepper bleibt für die lineare Reihenfolge zuständig." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-review-overview-heading">Comfortable · vier Zustände</strong><span>x-ui.review-overview</span></div>
            <x-ui.review-overview title="Medienerfassung – Prüfbereiche" :items="[
                ['title' => 'Grundangaben und ISBN', 'description' => 'Titel und Medientyp wurden kontrolliert.', 'status' => 'complete', 'href' => '#bc-core-forms'],
                ['title' => 'Ein außergewöhnlich langer Katalogtitel und die vielen Angaben zur Erschließung', 'description' => 'Der Abschnitt ist zurzeit in Bearbeitung.', 'status' => 'current', 'href' => '#bc-core-details'],
                ['title' => 'Exemplar und Standort', 'description' => 'Angaben müssen noch einmal geprüft werden.', 'status' => 'attention', 'href' => '#bc-core-copy-rows'],
                ['title' => 'Abschluss und Freigabe', 'description' => 'Noch keine Bearbeitung möglich.', 'status' => 'pending', 'disabled' => true],
            ]">
                <x-slot:actions><a href="#bc-core-step-actions" class="bc-button bc-button--secondary bc-button--sm">Lineare Schrittaktionen vergleichen</a></x-slot:actions>
            </x-ui.review-overview>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · noch keine Bereiche</strong><span>density=compact</span></div>
            <x-ui.review-overview title="Leere Prüfung" density="compact" :items="[]" />
            <p class="bc-core-gallery-v11__note">Statuswerte kommen ausschließlich aus der aufrufenden Anwendung; die Komponente speichert oder verändert sie nicht.</p>
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.review-overview&gt;</code><span>items[] mit title, description, status: complete/current/attention/pending, href/disabled; title, label, actions, density. Statusfarben mit ausgeschriebenen Texten.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-change-review" aria-labelledby="bc-core-change-review-heading">
    <x-ui.section-heading eyebrow="51 · Vergleich" title="Änderungen vor einer Entscheidung klar nachvollziehen" description="Vorher und Nachher stehen als echte Definitionen nebeneinander beziehungsweise auf dem Handy untereinander. Status wird auch als Text vermittelt; es gibt keine Speicheraktion." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-change-review-heading">Comfortable · geändert, ergänzt, entfernt, gleich</strong><span>x-ui.change-review</span></div>
            <x-ui.change-review title="Fiktive Änderungen an einem Medienexemplar" :items="[
                ['label' => 'Titel', 'before' => 'Bücherwelten', 'after' => 'Bücherwelten: Eine ungewöhnlich lange Reise durch die vielen Räume der Stadtbibliothek und ihre historischen Bestände'],
                ['label' => 'Standort', 'before' => null, 'after' => 'Lesesaal, Regal B', 'status' => 'added'],
                ['label' => 'Interne Notiz', 'before' => 'Ausstellungsmaterial', 'after' => null, 'status' => 'removed'],
                ['label' => 'Medientyp', 'before' => 'Buch', 'after' => 'Buch'],
            ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · keine Vergleichsdaten</strong><span>density=compact</span></div>
            <x-ui.change-review title="Keine Änderungen" emptyLabel="Zu dieser Ansicht liegen keine Vergleichsdaten vor." density="compact" :items="[]" />
            <p class="bc-core-gallery-v11__note">Die Vergleichsdaten werden rein lesend übergeben. Eine künftige Freigabe oder Speicherung bleibt ein separater, zugriffsgeschützter Fachworkflow.</p>
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.change-review&gt;</code><span>items[] mit label, before, after, optional status: changed/added/removed/unchanged; title, label, beforeLabel, afterLabel, emptyLabel, actions, density.</span></div>
</section>
