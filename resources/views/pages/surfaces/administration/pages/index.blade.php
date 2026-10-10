@php
    $urls = ['impressum' => '/impressum', 'datenschutz' => '/datenschutz', 'barrierefreiheit' => '/barrierefreiheit'];
@endphp

<x-app-shell surface="administration" title="Seiten">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Informationsseiten"
        lead="Impressum, Datenschutzerklärung und Erklärung zur Barrierefreiheit. Die Texte erscheinen im Fußbereich aller Seiten."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
        <a href="{{ route('administration.blocks.index') }}">Textbausteine (Hinweise auf Startseite und Katalog)</a>
    </div>

    @if (session('school_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('school_success') }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="pages-heading">
        <div class="bc-section-heading"><h2 id="pages-heading">Seiten</h2></div>
        <table class="bc-calendar-table">
            <thead>
                <tr><th scope="col">Seite</th><th scope="col">Stand</th><th scope="col"><span class="bc-visually-hidden">Aktion</span></th></tr>
            </thead>
            <tbody>
                @foreach ($pages as $page)
                    <tr>
                        <th scope="row">{{ $page->title }} <small class="bc-public-metadata-source">{{ $urls[$page->slug] ?? '/'.$page->slug }}</small></th>
                        <td>
                            @if ($page->is_placeholder)
                                <x-ui.badge variant="warning">Platzhalter</x-ui.badge>
                            @else
                                <x-ui.badge variant="success">Ausgefüllt</x-ui.badge>
                                <small class="bc-public-metadata-source">zuletzt {{ $page->updated_at->format('d.m.Y') }}</small>
                            @endif
                        </td>
                        <td><x-ui.button href="{{ route('administration.pages.edit', ['slug' => $page->slug]) }}" variant="secondary">Bearbeiten</x-ui.button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</x-app-shell>
