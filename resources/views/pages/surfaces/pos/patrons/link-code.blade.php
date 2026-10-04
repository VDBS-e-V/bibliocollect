<x-app-shell surface="pos" title="Onlinekonto-Code">
    <x-ui.page-header
        kicker="Ausleihkonto"
        title="Einmalcode ausgegeben"
        :lead="$patron->displayName().' · '.$patron->library_number"
    />

    <section class="bc-one-time-secret" aria-labelledby="one-time-code-heading">
        <p class="bc-eyebrow">Nur jetzt anzeigen</p>
        <h2 id="one-time-code-heading">Code für die Konto-Aktivierung</h2>
        <p class="bc-code-output" aria-label="Einmalcode">{{ $code }}</p>
        <p>Gültig bis <strong>{{ $expiresAt->format('d.m.Y H:i') }} Uhr</strong>.</p>
        <p>Gib den Code persönlich an die betreffende Person aus. Der Klartext-Code wird nicht in BiblioCollect gespeichert und nach Verlassen dieser Seite nicht erneut angezeigt.</p>
    </section>

    <div class="bc-action-row">
        <x-ui.button href="{{ route('pos.patrons.show', ['patronId' => $patron->getKey()]) }}">Zurück zum Ausleihkonto</x-ui.button>
        <x-ui.button href="{{ route('pos.patrons.index') }}" variant="secondary">Neue Suche</x-ui.button>
    </div>
</x-app-shell>
