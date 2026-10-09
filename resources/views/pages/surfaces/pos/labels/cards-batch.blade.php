<x-app-shell surface="pos" :title="'Charge '.$batch.' drucken'">
    <x-ui.page-header
        kicker="Ausleihkonten"
        :title="'Charge '.$batch.' drucken'"
        :lead="$free.' freie Ausweise. Gedruckt wird auf Avery Zweckform C32016 (85 × 54 mm, 10 Karten je Bogen), randlos.'"
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.labels.cards') }}">← Zurück zu den Ausweisen</a>
        <a href="{{ route('pos.labels.cards.designs') }}">Motive verwalten</a>
    </div>

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="post" action="{{ route('pos.labels.cards.print', ['batch' => $batch]) }}" target="_blank" class="bc-labels-form">
        @csrf

        <section class="bc-content-section" aria-labelledby="mode-heading">
            <div class="bc-section-heading"><h2 id="mode-heading">1. Wie soll gedruckt werden?</h2></div>
            <fieldset class="bc-print-modes">
                <legend class="bc-visually-hidden">Druckart</legend>
                <label class="bc-print-mode">
                    <input type="radio" name="side" value="beide" checked>
                    <span>
                        <strong>Beidseitig</strong>
                        <small>Vorder- und Rückseiten in einem Druckauftrag. Im Druckdialog „Beidseitig“ mit „Wenden an der langen Kante“ wählen.</small>
                    </span>
                </label>
                <label class="bc-print-mode">
                    <input type="radio" name="side" value="vorder">
                    <span>
                        <strong>Einseitig: nur Vorderseiten</strong>
                        <small>Zum Beispiel, wenn die Rückseiten schon vorbereitet sind oder weiß bleiben.</small>
                    </span>
                </label>
                <label class="bc-print-mode">
                    <input type="radio" name="side" value="rueck">
                    <span>
                        <strong>Einseitig: nur Rückseiten</strong>
                        <small>Für den zweiten Durchgang von Hand: Bogen mit bedruckter Vorderseite wieder einlegen, an der langen Kante gewendet.</small>
                    </span>
                </label>
            </fieldset>
            <x-ui.input label="Erste Position auf dem Bogen (1–10)" name="start" id="start" type="number" min="1" max="10" value="1" hint="Für angebrochene Bögen: Die ersten Plätze bleiben frei. Bei beidseitigem Druck gilt die Position für beide Seiten." />
        </section>

        <section class="bc-content-section" aria-labelledby="motifs-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="motifs-heading">2. Welche Motive?</h2>
                <span>{{ $unassigned }} Ausweise ohne Motiv</span>
            </div>
            @if ($motifs->isEmpty())
                <p class="bc-section-copy">Kein verwendbares Motiv: Die Karten werden auf weißem Grund gedruckt.</p>
            @else
                <p class="bc-section-copy">
                    Jeder Ausweis behält sein Motiv auf Vorder- und Rückseite und bei Nachdrucken. Neue Ausweise bekommen beim ersten Druck zufällig ein Motiv, „Mehr von diesem Motiv“ gilt doppelt, „Auslassen“ gar nicht.
                    Die Einstellung steht bei den <a href="{{ route('pos.labels.cards.designs') }}">Motiven</a>.
                </p>
                <ul class="bc-card-designs bc-card-designs--compact">
                    @foreach ($motifs as $motif)
                        <li class="bc-card-design">
                            <div class="bc-card-design__pair">
                                <img src="{{ $motif->frontUrl() }}" alt="Vorderseite des Motivs {{ $motif->name }}" width="120" loading="lazy">
                                <img src="{{ $motif->backUrl() }}" alt="Rückseite des Motivs {{ $motif->name }}" width="120" loading="lazy">
                            </div>
                            <div>
                                <strong>{{ $motif->name }}</strong>
                                <span>{{ $motif->distributionLabel() }}@if ($unassigned > 0 && isset($shares[(string) $motif->getKey()])) · etwa {{ (int) round($shares[(string) $motif->getKey()]) }} %@endif</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="bc-content-section" aria-labelledby="go-heading">
            <div class="bc-section-heading"><h2 id="go-heading">3. Drucken</h2></div>
            <p class="bc-section-copy">Im Druckdialog: A4, Maßstab 100 %, Ränder „Keine“, keine Kopf- und Fußzeilen, Hintergrundgrafiken an. Erst auf Normalpapier probedrucken.</p>
            <x-ui.button type="submit">Druckansicht öffnen</x-ui.button>
        </section>
    </form>
</x-app-shell>
