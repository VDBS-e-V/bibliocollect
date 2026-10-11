<x-app-shell surface="public" title="Designstudio · BiblioCollect">
    @php
        $screen = $screen ?? 'index';
        $nav = [
            ['key' => 'index', 'label' => 'Übersicht', 'route' => 'public.design-lab'],
            ['key' => 'designsystem', 'label' => 'Designsystem', 'route' => 'public.design-lab.designsystem'],
            ['key' => 'startseite', 'label' => 'Startseite', 'route' => 'public.design-lab.home'],
            ['key' => 'medien', 'label' => 'Mediendetail', 'route' => 'public.design-lab.media'],
            ['key' => 'assistent', 'label' => 'Assistent', 'route' => 'public.design-lab.wizard'],
            ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'public.design-lab.dashboard'],
            ['key' => 'komponenten', 'label' => 'Komponenten', 'route' => 'public.design-lab.components'],
        ];
        $wizardSteps = [
            ['label' => 'Datei wählen', 'description' => 'Importquelle', 'status' => 'done'],
            ['label' => 'Ziel auswählen', 'description' => 'Bibliothek', 'status' => 'done'],
            ['label' => 'Bestände zuordnen', 'description' => 'Datensätze prüfen', 'status' => 'current'],
            ['label' => 'Felder prüfen', 'description' => 'Pflichtangaben', 'status' => 'upcoming'],
            ['label' => 'Bestätigen', 'description' => 'Übernahme', 'status' => 'upcoming'],
        ];
    @endphp

    <div class="bc-design-lab">
        <nav class="bc-design-lab__nav" aria-label="Designstudien auswählen">
            <a class="bc-design-lab__mark" href="{{ route('public.design-lab') }}">BiblioCollect <span>Designstudio</span></a>
            <div class="bc-design-lab__navlinks">
                @foreach ($nav as $item)
                    <a @class(['bc-design-lab__navlink', 'bc-design-lab__navlink--active' => $screen === $item['key']])
                       href="{{ route($item['route']) }}"
                       @if ($screen === $item['key']) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
            </div>
        </nav>
        @if ($screen === 'designsystem')
            <p class="bc-design-lab__notice">Die folgenden Zahlen stammen aus dem <strong>Designsystem-Masterplan</strong> und beschreiben die geplante Struktur – keine Live-Statistiken oder bereits vollständig umgestellten Seiten.</p>
        @else
            <p class="bc-design-lab__notice">Interaktive Seitennavigation, aber ausschließlich <strong>fiktive Beispieldaten</strong>. Es werden keine Ausleihen, Importe oder Verwaltungsaktionen ausgeführt.</p>
        @endif

        @if ($screen === 'index')
            <header class="bc-design-lab__intro">
                <span class="bc-design-lab__kicker">VDBS / BiblioCollect · Designstudie 04</span>
                <h1>Ein System.<br><em>Mehr Raum für Ideen.</em></h1>
                <p>Vier eigenständige Beispielseiten und eine Komponentenbibliothek. Die Studie verbindet lebendige Überlagerungen nach deiner Oodi-Referenz mit einer modernen Prozessachse, Bento-Statistiken und klaren Statusmeldungen.</p>
            </header>

            <a class="bc-design-lab__system-banner" href="{{ route('public.design-lab.designsystem') }}">
                <span class="bc-design-lab__system-banner-copy">
                    <small>Neu · Grafischer Masterplan</small>
                    <strong>Das gesamte BiblioCollect Designsystem auf einer Seite.</strong>
                    <span>15 Familien · 323 Planungspositionen · 22 Templates · 4 Anwendungsbereiche <span aria-hidden="true">↗</span></span>
                </span>
                <span class="bc-design-lab__system-banner-visual" aria-hidden="true">
                    <span>Tokens</span><span>Komponenten</span><span>Patterns</span><span>Templates</span><span>Fachseiten</span>
                </span>
            </a>
            <div class="bc-design-lab__overview">
                <a href="{{ route('public.design-lab.home') }}" class="bc-design-lab__overview-card bc-design-lab__overview-card--feature">
                    <span class="bc-design-lab__kicker">01 / Startseite</span>
                    <strong>Entdecken<br>beginnt hier.</strong>
                    <span>Editorial Overlap, Suche und Bibliotheksangebote <span aria-hidden="true">↗</span></span>
                    <img src="/brand/vdbs/figures-chain.svg" alt="" loading="lazy">
                </a>
                <a href="{{ route('public.design-lab.media') }}" class="bc-design-lab__overview-card bc-design-lab__overview-card--media">
                    <span class="bc-design-lab__kicker">02 / Medien</span>
                    <strong>Geschichten<br>mit Raum.</strong>
                    <span>Mediendetails und mehrzeilige Titel <span aria-hidden="true">↗</span></span>
                </a>
                <a href="{{ route('public.design-lab.wizard') }}" class="bc-design-lab__overview-card bc-design-lab__overview-card--wizard">
                    <span class="bc-design-lab__kicker">03 / Arbeitsablauf</span>
                    <strong>Schritt für<br>Schritt.</strong>
                    <span>Moderner horizontaler Prozess-Tracker <span aria-hidden="true">↗</span></span>
                </a>
                <a href="{{ route('public.design-lab.dashboard') }}" class="bc-design-lab__overview-card bc-design-lab__overview-card--dashboard">
                    <span class="bc-design-lab__kicker">04 / Dashboard</span>
                    <strong>Alles im<br>Überblick.</strong>
                    <span>Bento-Grid und Statusmeldungen <span aria-hidden="true">↗</span></span>
                </a>
                <a href="{{ route('public.design-lab.components') }}" class="bc-design-lab__overview-card bc-design-lab__overview-card--library">
                    <span class="bc-design-lab__kicker">05 / Komponenten</span>
                    <strong>Die Bausteine<br>dahinter.</strong>
                    <span>Vier wiederverwendbare Patterns und weitere UI-Elemente <span aria-hidden="true">↗</span></span>
                </a>
            </div>
        @elseif ($screen === 'designsystem')
            @include('pages.partials.design-system-map')
        @elseif ($screen === 'startseite')
            <header class="bc-design-lab__page-head">
                <span class="bc-design-lab__kicker">Musterseite 01 · Öffentlich</span>
                <h1>Einladend statt überladen.</h1>
                <p>Große Suchfläche mit bewusst reduzierten Direktzugriffen und einer überlappenden Bibliotheksgeschichte.</p>
            </header>
            <section class="bc-design-lab__home-hero" aria-label="Bibliothek entdecken">
                <div>
                    <span class="bc-design-lab__kicker">Deine Schulbibliothek</span>
                    <h2>Hier beginnt<br><em>deine nächste</em><br>Geschichte.</h2>
                    <p>Medien finden, Neues entdecken und deinen eigenen Ideen folgen.</p>
                    <div class="bc-design-lab__fake-search">
                        <span>Nach Büchern und Themen suchen …</span><span aria-hidden="true">⌕</span>
                    </div>
                    <small>Demonstration der Suchoberfläche · keine Suchabfrage</small>
                </div>
                <div class="bc-design-lab__home-visual" aria-hidden="true">
                    <div><x-library.media-cover title="Was wäre, wenn?" author="M. Zukunft" size="hero" variant="mint" /></div>
                    <div><x-library.media-cover title="Zwischen den Zeilen" author="Lena Beispiel" size="hero" variant="plum" /></div>
                </div>
            </section>
            <div class="bc-design-lab__section-intro">
                <span class="bc-design-lab__kicker">Schnell zum Ziel</span>
                <h2>Womit möchtest du beginnen?</h2>
            </div>
            <div class="bc-design-lab__services">
                <a href="{{ route('public.catalog.index') }}"><strong>Medien entdecken</strong><span>Den echten Katalog öffnen <b aria-hidden="true">↗</b></span></a>
                <a href="{{ route('public.series.index') }}"><strong>Buchreihen entdecken</strong><span>Serien und Reihen ansehen <b aria-hidden="true">↗</b></span></a>
                <a href="{{ route('public.wishes.create') }}"><strong>Buchwunsch</strong><span>Neue Medien vorschlagen <b aria-hidden="true">↗</b></span></a>
            </div>
            <div class="bc-design-lab__section-intro">
                <span class="bc-design-lab__kicker">Feature Overlap</span>
                <h2>Bibliothek ist mehr als ein Regal.</h2>
            </div>
            <x-library.feature-overlap
                eyebrow="Mehr entdecken"
                title="Ein Ort für Ideen, Geschichten und Begegnungen."
                description="Wie in deiner Oodi-Referenz liegt die Textfläche bewusst über dem visuellen Bereich. Die Komponente funktioniert auch mit einem echten Foto einer Schulbibliothek."
                :href="route('public.catalog.index')"
                action-label="Im Katalog stöbern"
                tone="mint"
            />
            <div class="bc-design-lab__section-intro">
                <span class="bc-design-lab__kicker">Servicehinweis</span><h2>Kurz und verständlich.</h2>
            </div>
            <x-library.notice-panel variant="info" title="Öffnungszeiten">
                <p>Hier würden die tatsächlichen Öffnungszeiten und Schließtage der jeweiligen Schule angezeigt. Keine erfundenen Stundenpläne.</p>
            </x-library.notice-panel>
        @elseif ($screen === 'medien')
            <header class="bc-design-lab__page-head">
                <span class="bc-design-lab__kicker">Musterseite 02 · Mediendetail</span>
                <h1>Titel, die Raum bekommen.</h1>
                <p>Lange Medientitel werden lesbar umgebrochen. Die Verfügbarkeit ist von bibliografischen Angaben getrennt.</p>
            </header>
            <x-library.feature-overlap
                eyebrow="Medien entdecken"
                title="Eine Geschichte über das Suchen, Finden und Wiedersehen"
                description="Ein bewusst langer, fiktiver Titel demonstriert mehrzeilige Darstellung und großzügige Inhaltsflächen."
                tone="neutral"
            >
                <x-slot:media>
                    <div class="bc-design-lab__media-cover-wall">
                        <div class="bc-design-lab__media-cover-book"><x-library.media-cover
                            title="Eine Geschichte über das Suchen, Finden und Wiedersehen"
                            author="A. Beispielautor:in"
                            size="hero"
                            variant="ink"
                        /></div>
                    </div>
                </x-slot:media>
                <div class="bc-design-lab__media-meta">
                    <span>Jugendroman</span><span>Deutsch</span><span>Beispielbestand</span>
                </div>
            </x-library.feature-overlap>
            <div class="bc-design-lab__media-details">
                <div>
                    <div class="bc-design-lab__section-intro">
                        <span class="bc-design-lab__kicker">Zum Inhalt</span><h2>Worum geht es?</h2>
                    </div>
                    <p>In dieser Beispielansicht geht es darum, eine gut lesbare Detailseite zu zeigen: mit großzügigem Satz, konsistenten Abständen und klar hervorgehobenen bibliografischen Angaben.</p>
                    <dl class="bc-design-lab__definition-list">
                        <div><dt>Autor:in</dt><dd>A. Beispielautor:in</dd></div>
                        <div><dt>Medientyp</dt><dd>Buch</dd></div>
                        <div><dt>Sprache</dt><dd>Deutsch</dd></div>
                        <div><dt>Signatur</dt><dd>DEMO – keine echte Signatur</dd></div>
                    </dl>
                </div>
                <aside class="bc-design-lab__availability">
                    <span class="bc-design-lab__kicker">Bestand · Beispiel</span>
                    <h3>Verfügbarkeit</h3>
                    <x-library.notice-panel variant="success" title="Ein Exemplar verfügbar" compact>
                        <p>Die Verfügbarkeit ist hier fiktiv. In der echten Titeldetailseite kommt sie aus BiblioCollect.</p>
                    </x-library.notice-panel>
                    <div class="bc-design-lab__pseudo-button" aria-disabled="true">Vormerken (nur Vorschau)</div>
                </aside>
            </div>
        @elseif ($screen === 'assistent')
            <header class="bc-design-lab__page-head">
                <span class="bc-design-lab__kicker">Musterseite 03 · Arbeitsabläufe</span>
                <h1>Schritt für Schritt. Ohne Umwege.</h1>
                <p>Die Prozessachse orientiert sich an deiner Referenz und verwendet klare Zustände statt vieler Rahmen und Kacheln.</p>
            </header>
            <section class="bc-design-lab__wizard">
                <div class="bc-design-lab__wizard-heading">
                    <div><span class="bc-design-lab__kicker">Medienimport · Vorschau</span><h2>Import vorbereiten</h2></div>
                    <span class="bc-design-lab__wizard-count">Schritt 3 von 5</span>
                </div>
                <x-library.process-stepper :steps="$wizardSteps" label="Schritte des Beispielimports" style="--bc-steps: 5;" />
                <div class="bc-design-lab__wizard-content">
                    <div>
                        <span class="bc-design-lab__kicker">Aktueller Schritt</span>
                        <h3>Bestände zuordnen</h3>
                        <p>Prüfe, ob die Angaben aus deiner Importdatei zu den Feldern in BiblioCollect passen. Für das Designbeispiel werden keine Dateien gelesen oder verändert.</p>
                        <div class="bc-design-lab__assignment">
                            <div><strong>Importfeld</strong><strong>Zielfeld</strong></div>
                            <div><span>Titel</span><span>Medientitel</span></div>
                            <div><span>Autor</span><span>Verantwortliche Person</span></div>
                            <div><span>ISBN</span><span>Identifikationsnummer</span></div>
                        </div>
                    </div>
                    <aside>
                        <x-library.notice-panel variant="info" title="Noch keine Änderungen">
                            <p>Der Assistent befindet sich im Prüfmodus. Die Übernahme würde erst nach der Bestätigung erfolgen.</p>
                        </x-library.notice-panel>
                    </aside>
                </div>
                <div class="bc-design-lab__wizard-footer">
                    <button type="button" disabled class="bc-design-lab__ghost-button">Zurück (Demo)</button>
                    <button type="button" disabled class="bc-design-lab__primary-button">Weiter (Demo) <span aria-hidden="true">→</span></button>
                </div>
            </section>
            <div class="bc-design-lab__section-intro"><span class="bc-design-lab__kicker">Varianten</span><h2>Auch Fehler bleiben gut verständlich.</h2></div>
            <x-library.notice-panel variant="error" title="Ein Feld konnte nicht zugeordnet werden">
                <p>Ein Fehler wird in Text und Symbol kommuniziert, nicht allein über die Farbe. Diese Meldung ist Teil der Vorschau.</p>
            </x-library.notice-panel>
        @elseif ($screen === 'dashboard')
            <header class="bc-design-lab__page-head">
                <span class="bc-design-lab__kicker">Musterseite 04 · Dashboard</span>
                <h1>Das Wichtige fällt auf.</h1>
                <p>Unterschiedliche Kachelgrößen machen die Oberfläche lebendig, ohne die Bedeutung der Kennzahlen zu verwischen.</p>
            </header>
            <div class="bc-design-lab__toolbar"><strong>Bibliothek im Überblick</strong><span>Demodaten · Kein Livebetrieb</span></div>
            <div class="bc-metric-bento">
                <x-library.stat-tile class="bc-stat-tile--wide" tone="purple" label="Aktive Ausleihen" value="87" detail="In dieser Demo von Schülerinnen und Schülern ausgeliehene Medien." eyebrow="Bibliotheksbetrieb" />
                <x-library.stat-tile tone="neutral" label="Vormerkungen" value="12" detail="Offene Reservierungen" />
                <x-library.stat-tile tone="mint" label="Offene Aufgaben" value="4" detail="Zum Prüfen und Bearbeiten" />
                <x-library.stat-tile class="bc-stat-tile--wide" tone="neutral" label="Medien im Bestand" value="1.248" detail="Bücher und andere Medien · fiktiver Beispielwert" />
                <x-library.stat-tile tone="dark" label="Überfällige Medien" value="6" detail="Rückgabefrist überschritten" />
                <x-library.stat-tile tone="neutral" label="Neue Buchwünsche" value="3" detail="Warten auf Sichtung" />
            </div>
            <div class="bc-design-lab__two-panel">
                <section>
                    <div class="bc-design-lab__section-intro">
                        <span class="bc-design-lab__kicker">Prioritäten</span><h2>Nächste Aufgaben</h2>
                    </div>
                    <div class="bc-design-lab__task-row"><span><strong>Buchwünsche prüfen</strong><small>3 neue Vorschläge</small></span><span aria-hidden="true">↗</span></div>
                    <div class="bc-design-lab__task-row"><span><strong>Rückgaben prüfen</strong><small>6 Fristen überschritten</small></span><span aria-hidden="true">↗</span></div>
                    <div class="bc-design-lab__task-row"><span><strong>Bestand durchsehen</strong><small>4 offene Aufgaben</small></span><span aria-hidden="true">↗</span></div>
                </section>
                <section>
                    <div class="bc-design-lab__section-intro">
                        <span class="bc-design-lab__kicker">Systemstatus</span><h2>Alles im Blick</h2>
                    </div>
                    <x-library.notice-panel variant="success" title="System bereit">
                        <p>Beispiel einer Erfolgsmeldung mit sanfter Markenfläche und klarer Symbolik.</p>
                    </x-library.notice-panel>
                    <x-library.notice-panel variant="warning" title="Prüfung empfohlen" compact class="bc-design-lab__notice-spaced">
                        <p>Fiktive Erinnerung an eine noch offene Aufgabe.</p>
                    </x-library.notice-panel>
                </section>
            </div>
        @elseif ($screen === 'komponenten')
            <header class="bc-design-lab__page-head">
                <span class="bc-design-lab__kicker">Komponentenbibliothek · VDBS</span>
                <h1>Einmal entwerfen. Überall verwenden.</h1>
                <p>Die vier Patterns sind echte Blade-Komponenten. Du siehst unterschiedliche Varianten, Zustände und Beispiele für lange Titel.</p>
            </header>
            <div class="bc-design-lab__component-section">
                <div class="bc-design-lab__section-intro"><span class="bc-design-lab__kicker">Pattern 01</span><h2>Feature Overlap</h2><p>Für Einstiege, Themen und besondere Inhalte. Desktop überlappend, mobil untereinander.</p></div>
                <x-library.feature-overlap eyebrow="Bibliothek entdecken" title="Geschichten verbinden Menschen." description="Großzügige Bildfläche und eine bewusste Überlagerung statt gleich breiter nebeneinanderliegender Kästen." tone="mint" />
                <div class="bc-design-lab__token-note"><code>&lt;x-library.feature-overlap&gt;</code> · Varianten: mint, purple, neutral · Prop: reverse · benannter Media-Slot</div>
            </div>
            <div class="bc-design-lab__component-section">
                <div class="bc-design-lab__section-intro"><span class="bc-design-lab__kicker">Pattern 02</span><h2>Process Stepper</h2><p>Einheitliche Progressionsachse, abgeschlossene und aktuelle Schritte.</p></div>
                <div class="bc-design-lab__component-pad">
                    <x-library.process-stepper :steps="$wizardSteps" label="Fünfstufiger Beispielprozess" style="--bc-steps: 5;" />
                </div>
                <div class="bc-design-lab__token-note"><code>&lt;x-library.process-stepper&gt;</code> · status: done / current / upcoming / error · aria-current="step"</div>
            </div>
            <div class="bc-design-lab__component-section">
                <div class="bc-design-lab__section-intro"><span class="bc-design-lab__kicker">Pattern 03</span><h2>Bento-Metriken</h2><p>Flexible Größen ohne willkürlich neue Farben oder erfundene Trends.</p></div>
                <div class="bc-metric-bento">
                    <x-library.stat-tile class="bc-stat-tile--wide" tone="purple" label="Medienbestand" value="1.248" detail="Breites Modul" />
                    <x-library.stat-tile tone="mint" label="Ausleihen" value="87" detail="Kompaktes Modul" />
                    <x-library.stat-tile tone="neutral" label="Vormerkungen" value="12" detail="Neutrales Modul" />
                </div>
                <div class="bc-design-lab__token-note"><code>&lt;x-library.stat-tile&gt;</code> · tones: neutral / mint / purple / dark · wide / tall per CSS-Klasse</div>
            </div>
            <div class="bc-design-lab__component-section">
                <div class="bc-design-lab__section-intro"><span class="bc-design-lab__kicker">Pattern 04</span><h2>Meldungen und Feedback</h2><p>Sanfte Flächen, Icon und verständlicher Text – auch ohne Farbsehen.</p></div>
                <div class="bc-design-lab__notices">
                    <x-library.notice-panel variant="info" title="Wichtiger Hinweis"><p>Hier findest du zusätzliche Informationen für einen Vorgang.</p></x-library.notice-panel>
                    <x-library.notice-panel variant="success" title="Alles erledigt"><p>Ein Vorgang wurde erfolgreich abgeschlossen – in dieser Ansicht nur als Beispiel.</p></x-library.notice-panel>
                    <x-library.notice-panel variant="warning" title="Bitte prüfen"><p>Ein weiterer Schritt ist erforderlich.</p></x-library.notice-panel>
                    <x-library.notice-panel variant="error" title="Vorgang fehlgeschlagen"><p>Beschreibe genau, was schiefging und wie es weitergeht.</p></x-library.notice-panel>
                </div>
                <div class="bc-design-lab__token-note"><code>&lt;x-library.notice-panel&gt;</code> · info / success / warning / error · optional compact</div>
            </div>
            <div class="bc-design-lab__component-section">
                <div class="bc-design-lab__section-intro"><span class="bc-design-lab__kicker">Bereits vorhanden</span><h2>Mediencover und UI-Elemente</h2><p>Vier farbliche Gestaltungen, auch für lange und mehrzeilige Titel.</p></div>
                <div class="bc-design-lab__covers">
                    <div><x-library.media-cover title="Zwischen den Zeilen" author="Lena Beispiel" variant="plum" /><strong>Plum</strong></div>
                    <div><x-library.media-cover title="Was wäre, wenn?" author="M. Zukunft" variant="mint" /><strong>Mint</strong></div>
                    <div><x-library.media-cover title="Die Stadt bei Nacht" author="Noa Muster" variant="ink" /><strong>Ink</strong></div>
                    <div><x-library.media-cover title="Das geheime Archiv" author="Alex Wörter" variant="sand" /><strong>Sand</strong></div>
                    <div><x-library.media-cover title="Die wunderbare und unglaublich aufregende Geschichte von einer Reise in eine unbekannte Welt" author="A. Beispielautor:in" variant="plum" /><strong>Langer Titel</strong></div>
                </div>
                <div class="bc-design-lab__token-note">Originalcover werden weiterhin bevorzugt. Der vorhandene SVG-Cache und die vier Farbstile bleiben erhalten.</div>
            </div>
        @endif
    </div>
</x-app-shell>
