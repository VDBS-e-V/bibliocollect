{{-- Statische Designstudie – keine authentifizierten Kontodaten und keine Mutation. --}}
<div class="bc-portal-study">
    <header class="bc-portal-study__welcome">
        <div class="bc-portal-study__welcome-copy">
            <span class="bc-design-lab__kicker">Musterseite 05 · Persönliches Portal · Hybrid</span>
            <h1>Meine Bibliothek.</h1>
            <p>Behalte deine Rückgaben im Blick. Alle wichtigen Informationen zu deinen ausgeliehenen Medien stehen hier an erster Stelle.</p>
        </div>
        <div class="bc-portal-study__welcome-aside">
            <strong>Was steht heute an?</strong>
            <p>Prüfe zuerst deine Rückgabefristen – danach folgen Vormerkungen und weitere Angebote.</p>
        </div>
    </header>

    <p class="bc-portal-study__disclaimer"><strong>Designbeispiel:</strong> Alle Medien, Daten, Fristen und Zustände sind fiktiv. Verlängern und Stornieren sind in dieser Vorschau deaktiviert.</p>

    <section class="bc-portal-study__priority" aria-labelledby="portal-study-priority-heading">
        <header class="bc-portal-study__section-head">
            <div><span class="bc-design-lab__kicker">01 · Zuerst erledigen</span><h2 id="portal-study-priority-heading">Deine nächsten Rückgaben</h2></div>
            <span class="bc-portal-study__meta">Beispielstatus · nicht live</span>
        </header>
        <div class="bc-portal-study__deadline-layout">
            <article class="bc-portal-study__deadline bc-portal-study__deadline--urgent">
                <div class="bc-portal-study__deadline-upper">
                    <x-ui.badge variant="danger">Überfällig</x-ui.badge>
                    <span>Rückgabe vor 2 Tagen</span>
                </div>
                <h3>Die Stadt bei Nacht</h3>
                <p>Dieses Medium ist in der Beispielansicht überfällig. Bitte gib es möglichst bald in deiner Bibliothek zurück.</p>
                <span class="bc-portal-study__deadline-caption">Verlängerung ist je nach Ausleihregel und Status möglich.</span>
            </article>
            <div class="bc-portal-study__deadline-side">
                <article class="bc-portal-study__deadline bc-portal-study__deadline--soon">
                    <span class="bc-portal-study__relative">In 3 Tagen</span>
                    <h3>Zwischen den Zeilen</h3>
                    <p>Nächste Rückgabe</p>
                </article>
                <article class="bc-portal-study__deadline bc-portal-study__deadline--quiet">
                    <span class="bc-portal-study__relative">In 12 Tagen</span>
                    <h3>Was wäre, wenn?</h3>
                    <p>Weitere Rückgabe</p>
                </article>
            </div>
        </div>
    </section>

    <section class="bc-portal-study__loans" aria-labelledby="portal-study-loans-heading">
        <header class="bc-portal-study__section-head">
            <div><span class="bc-design-lab__kicker">02 · Laufende Ausleihen</span><h2 id="portal-study-loans-heading">Alle ausgeliehenen Medien</h2></div>
            <span class="bc-portal-study__meta">3 Medien · kompakte Liste</span>
        </header>
        <div class="bc-portal-study__loan-list" role="list">
            @foreach ([
                ['title' => 'Die Stadt bei Nacht', 'author' => 'Noa Muster', 'variant' => 'ink', 'due' => 'Vor 2 Tagen', 'status' => 'Überfällig', 'variantBadge' => 'danger', 'allowed' => false],
                ['title' => 'Zwischen den Zeilen', 'author' => 'Lena Beispiel', 'variant' => 'plum', 'due' => 'In 3 Tagen', 'status' => 'Ausgeliehen', 'variantBadge' => 'neutral', 'allowed' => true],
                ['title' => 'Die wunderbare und unglaublich aufregende Geschichte von einer Reise in eine unbekannte Welt', 'author' => 'A. Beispielautor:in', 'variant' => 'mint', 'due' => 'In 12 Tagen', 'status' => 'Ausgeliehen', 'variantBadge' => 'neutral', 'allowed' => true],
            ] as $loan)
                <article class="bc-portal-study__loan" role="listitem">
                    <div class="bc-portal-study__loan-cover">
                        <x-library.media-cover :title="$loan['title']" :author="$loan['author']" :variant="$loan['variant']" size="small" />
                    </div>
                    <div class="bc-portal-study__loan-copy">
                        <h3>{{ $loan['title'] }}</h3>
                        <p>{{ $loan['author'] }}</p>
                    </div>
                    <div class="bc-portal-study__loan-due">
                        <small>Rückgabe</small>
                        <strong>{{ $loan['due'] }}</strong>
                    </div>
                    <div class="bc-portal-study__loan-status"><x-ui.badge :variant="$loan['variantBadge']">{{ $loan['status'] }}</x-ui.badge></div>
                    <div class="bc-portal-study__loan-action">
                        @if ($loan['allowed'])
                            <button class="bc-portal-study__demo-button" type="button" disabled>Verlängern (Demo)</button>
                        @else
                            <span class="bc-portal-study__unavailable">Verlängerung prüfen</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        <p class="bc-portal-study__support">Im echten Portal wird die Verlängerung nur angeboten, wenn die vorhandenen Ausleihregeln sie zulassen. Gründe für eine Sperre bleiben lesbar.</p>
    </section>

    <section class="bc-portal-study__reservations" aria-labelledby="portal-study-reservations-heading">
        <header class="bc-portal-study__section-head">
            <div><span class="bc-design-lab__kicker">03 · Als Nächstes</span><h2 id="portal-study-reservations-heading">Vormerkungen</h2></div>
            <span class="bc-portal-study__meta">2 Beispiel-Vormerkungen</span>
        </header>
        <div class="bc-portal-study__reservation-grid">
            <article class="bc-portal-study__reservation">
                <x-ui.badge variant="success">Abholbereit</x-ui.badge>
                <h3>Das geheime Archiv</h3>
                <p>Dein Medium kann abgeholt werden. Bitte beachte die Abholfrist.</p>
                <strong>Abholung innerhalb der angegebenen Frist</strong>
            </article>
            <article class="bc-portal-study__reservation">
                <x-ui.badge variant="neutral">Wartet</x-ui.badge>
                <h3>Geschichten vom Morgen</h3>
                <p>Du stehst noch in der Warteschlange. Den aktuellen Rang zeigt das echte Portal.</p>
                <strong>Position 2 · Beispiel</strong>
            </article>
        </div>
    </section>

    <section class="bc-portal-study__extras" aria-labelledby="portal-study-extras-heading">
        <header class="bc-portal-study__section-head">
            <div><span class="bc-design-lab__kicker">04 · Weitere Möglichkeiten</span><h2 id="portal-study-extras-heading">Mehr entdecken und verwalten</h2></div>
        </header>
        <div class="bc-portal-study__extras-grid">
            <a href="{{ route('public.catalog.index') }}"><strong>Im Katalog stöbern</strong><span>Neue Medien entdecken <span aria-hidden="true">↗</span></span></a>
            <a href="{{ route('public.series.index') }}"><strong>Buchreihen ansehen</strong><span>Weitere Geschichten finden <span aria-hidden="true">↗</span></span></a>
            <a href="{{ route('public.wishes.create') }}"><strong>Ein Buch vorschlagen</strong><span>Wünsche für die Bibliothek <span aria-hidden="true">↗</span></span></a>
        </div>
    </section>

    <section class="bc-portal-study__variants" aria-labelledby="portal-study-variants-heading">
        <header class="bc-portal-study__section-head"><div><span class="bc-design-lab__kicker">Zustände zur Designabnahme</span><h2 id="portal-study-variants-heading">Auch ohne Ausleihen verständlich</h2></div></header>
        <div class="bc-portal-study__variant-grid">
            <x-library.notice-panel variant="info" title="Noch keine Ausleihen">
                <p>Wenn keine Medien ausgeliehen sind, zeigen wir keine erfundene Rückgabefrist. Stattdessen erklären wir den nächsten Schritt und verlinken zum Katalog.</p>
            </x-library.notice-panel>
            <x-library.notice-panel variant="info" title="Konto noch nicht verknüpft">
                <p>Ausleihdaten werden erst angezeigt, wenn das Bibliothekskonto verknüpft ist. Diese Meldung soll oberhalb leerer Listen stehen.</p>
            </x-library.notice-panel>
        </div>
    </section>
</div>
