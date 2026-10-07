<x-app-shell surface="pos" title="Exemplar wieder verfügbar">
    <x-ui.page-header
        kicker="Ausleihe"
        title="Exemplar wieder verfügbar machen"
        lead="Dieses Exemplar ist im System als verloren oder beschädigt eingetragen. Wenn es wieder da ist oder repariert wurde, macht ein Klick es wieder ausleihbar."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.home') }}">← Zurück zum Arbeitsplatz</a>
    </div>

    <section class="bc-work-panel" aria-labelledby="found-heading">
        <div class="bc-section-heading"><h2 id="found-heading">{{ $copy->edition->title->preferred_title }}</h2></div>
        <dl class="bc-intake-summary">
            <dt>Inventarnummer</dt>
            <dd><strong>{{ $copy->barcode }}</strong></dd>
            <dt>Stand im System</dt>
            <dd>{{ $copy->status === \App\Modules\Catalog\Enums\CopyStatus::Lost ? 'Verloren' : 'Beschädigt' }}</dd>
        </dl>
        <p class="bc-section-copy">Wartet jemand auf diesen Titel, wird das Exemplar gleich für die erste Person zurückgelegt. Die Meldung nach dem Klick sagt, ob das der Fall war.</p>
        <form method="post" action="{{ route('pos.copy-found.store', ['barcode' => $copy->barcode]) }}">
            @csrf
            <x-ui.button type="submit">Wieder verfügbar machen</x-ui.button>
        </form>
    </section>
</x-app-shell>
