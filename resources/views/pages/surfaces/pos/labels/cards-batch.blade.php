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
        Die Motive werden zufällig und genau in den angegebenen Anteilen verteilt. Voreingestellt ist die Gleichverteilung.
        Ändere die Prozentwerte nur, wenn von einem Motiv zu viele übrig sind; sie gelten nur für diesen Druck. Die Summe muss 100 ergeben.
    </p>

    @foreach (['vorder' => ['Vorderseiten', $front, $frontShares], 'rueck' => ['Rückseiten', $back, $backShares]] as $side => [$title, $designs, $shares])
        <section class="bc-content-section" aria-labelledby="print-{{ $side }}-heading">
            <div class="bc-section-heading"><h2 id="print-{{ $side }}-heading">{{ $title }}</h2></div>
            <form method="post" action="{{ route('pos.labels.cards.print', ['batch' => $batch]) }}" target="_blank" class="bc-audit-filter">
                @csrf
                <input type="hidden" name="side" value="{{ $side }}">
                <x-ui.input label="Erste Position auf dem Bogen (1–10)" name="start" id="start-{{ $side }}" type="number" min="1" max="10" value="1" />
                @foreach ($designs as $design)
                    <x-ui.input :label="$design->name.' (%)'" name="motiv[{{ $design->getKey() }}]" id="motiv-{{ $side }}-{{ $design->getKey() }}" type="number" min="0" max="100" :value="$shares[(string) $design->getKey()] ?? 0" />
                @endforeach
                <x-ui.button type="submit">{{ $title }} drucken</x-ui.button>
            </form>
            @if ($designs->isEmpty())
                <p class="bc-section-copy">Kein aktives Motiv: Die Karten werden auf weißem Grund gedruckt.</p>
            @endif
        </section>
    @endforeach

    <p class="bc-section-copy">
        Beidseitig: erst die Vorderseiten drucken, den Bogen mit der bedruckten Seite wieder einlegen (Wenden an der langen Kante) und dann die Rückseiten drucken,
        mit derselben ersten Position. Im Druckdialog: A4, Maßstab 100 %, Ränder „Keine“, keine Kopf- und Fußzeilen. Erst auf Normalpapier probedrucken.
    </p>
</x-app-shell>
