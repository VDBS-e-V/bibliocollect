<x-app-shell surface="public" :title="$page->title">
    <article class="bc-content-page">
        <x-ui.page-header kicker="Information" :title="$page->title" />

        @if ($page->is_placeholder)
            <x-ui.alert title="Noch nicht ausgefüllt">Diese Seite enthält noch einen Platzhaltertext und muss vom Betreiber ergänzt werden.</x-ui.alert>
        @endif

        <div class="bc-content-page__body">{!! $html !!}</div>
    </article>
</x-app-shell>
