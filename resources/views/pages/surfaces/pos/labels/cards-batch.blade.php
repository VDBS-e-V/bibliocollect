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

    <p class="bc-section-copy">
        Jeder Ausweis hat ein Motiv für Vorder- und Rückseite. Die Motive werden beim ersten Druck zufällig und genau in den angegebenen Anteilen verteilt
        und am Ausweis gespeichert; beim Drucken der Rückseite und bei Nachdrucken bleibt das Motiv dasselbe. Voreingestellt ist die Gleichverteilung.
        Ändere die Prozentwerte nur, wenn von einem Motiv zu viele übrig sind; sie gelten nur für diesen Druck und nur für Ausweise ohne Motiv
        ({{ $unassigned }} in dieser Charge). Die Summe muss 100 ergeben.
    </p>

    <section class="bc-content-section" aria-labelledby="print-heading">
        <div class="bc-section-heading"><h2 id="print-heading">Drucken</h2></div>
        @foreach (['vorder' => 'Vorderseiten', 'rueck' => 'Rückseiten'] as $side => $title)
            <form method="post" action="{{ route('pos.labels.cards.print', ['batch' => $batch]) }}" target="_blank" class="bc-audit-filter">
                @csrf
                <input type="hidden" name="side" value="{{ $side }}">
                <x-ui.input label="Erste Position auf dem Bogen (1–10)" name="start" id="start-{{ $side }}" type="number" min="1" max="10" value="1" />
                @if ($unassigned > 0)
                    @foreach ($motifs as $motif)
                        <x-ui.input :label="$motif->name.' (%)'" name="motiv[{{ $motif->getKey() }}]" id="motiv-{{ $side }}-{{ $motif->getKey() }}" type="number" min="0" max="100" :value="$shares[(string) $motif->getKey()] ?? 0" />
                    @endforeach
                @endif
                <x-ui.button type="submit">{{ $title }} drucken</x-ui.button>
            </form>
        @endforeach
        @if ($motifs->isEmpty())
            <p class="bc-section-copy">Kein aktives Motiv: Die Karten werden auf weißem Grund gedruckt.</p>
        @endif
    </section>

    <p class="bc-section-copy">
        Beidseitig: erst die Vorderseiten drucken, den Bogen mit der bedruckten Seite wieder einlegen (Wenden an der langen Kante) und dann die Rückseiten drucken,
        mit derselben ersten Position. Im Druckdialog: A4, Maßstab 100 %, Ränder „Keine“, keine Kopf- und Fußzeilen. Erst auf Normalpapier probedrucken.
    </p>
</x-app-shell>
