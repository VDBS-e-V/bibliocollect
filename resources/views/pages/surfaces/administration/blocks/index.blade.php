<x-app-shell surface="administration" title="Textbausteine">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Textbausteine"
        lead="Kurze Hinweise, die du an festen Stellen der öffentlichen Seiten einblenden kannst, zum Beispiel Ferienöffnungszeiten. Optional mit Anfang und Ende."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.pages.index') }}">← Zu den Informationsseiten</a>
        <a href="{{ route('administration.home') }}">Verwaltung</a>
    </div>

    @if (session('school_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('school_success') }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="blocks-heading">
        <div class="bc-section-heading"><h2 id="blocks-heading">Bausteine</h2></div>
        <table class="bc-calendar-table">
            <thead>
                <tr><th scope="col">Baustein</th><th scope="col">Wo er erscheint</th><th scope="col">Stand</th><th scope="col"><span class="bc-visually-hidden">Aktion</span></th></tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <th scope="row">{{ $row['title'] }}</th>
                        <td>{{ $row['place'] }}</td>
                        <td>
                            @if ($row['visible'])
                                <x-ui.badge variant="success">Sichtbar</x-ui.badge>
                            @elseif ($row['block'] && $row['block']->is_active)
                                <x-ui.badge variant="warning">Eingeschaltet, außerhalb des Zeitraums</x-ui.badge>
                            @else
                                <x-ui.badge variant="neutral">Aus</x-ui.badge>
                            @endif
                            @if ($row['block'] && ($row['block']->visible_from || $row['block']->visible_until))
                                <small class="bc-public-metadata-source">
                                    {{ $row['block']->visible_from?->format('d.m.Y') ?? 'sofort' }} bis {{ $row['block']->visible_until?->format('d.m.Y') ?? 'unbefristet' }}
                                </small>
                            @endif
                        </td>
                        <td><x-ui.button href="{{ route('administration.blocks.edit', ['key' => $row['key']]) }}" variant="secondary">Bearbeiten</x-ui.button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</x-app-shell>
