<x-app-shell surface="portal" title="Leselisten">
    <x-ui.page-header
        kicker="Mein Konto"
        title="Leselisten"
        lead="Bücher, die Lehrkräfte für Klassen zusammengestellt haben, zum Beispiel eine Klassenlektüre oder Tipps zu einem Thema. Jede Liste hat auch einen Link, der ohne Konto funktioniert."
    />

    <div class="bc-context-actions">
        <a href="{{ route('portal.home') }}">← Zurück zur Übersicht</a>
    </div>

    @if (session('portal_success'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('portal_success') }}</x-ui.alert>
    @endif

    @if (session('portal_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('portal_error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Bitte prüfen">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if ($canManage)
        <section class="bc-content-section" aria-labelledby="mine-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="mine-heading">Meine Leselisten</h2>
                <span>{{ $mine->count() }}</span>
            </div>

            @if ($mine->isEmpty())
                <p class="bc-section-copy">Du hast noch keine Leseliste. Lege unten die erste an.</p>
            @else
                <ul class="bc-wish-list">
                    @foreach ($mine as $list)
                        <li class="bc-reading-item">
                            <a href="{{ route('portal.reading-lists.show', ['listId' => $list->getKey()]) }}"><strong>{{ $list->name }}</strong></a>
                            <span>
                                {{ $list->items_count }} Titel
                                · {{ $list->classes->isEmpty() ? 'keine Klasse' : $list->classes->pluck('name')->join(', ') }}
                                @if ($list->ends_on) · bis {{ $list->ends_on->format('d.m.Y') }} @endif
                            </span>
                            @if (! $list->is_published)<x-ui.badge variant="neutral">Ausgeschaltet</x-ui.badge>@endif
                            @if (! $list->isCurrent())<x-ui.badge variant="neutral">Abgelaufen</x-ui.badge>@endif
                        </li>
                    @endforeach
                </ul>
            @endif

            <h3>Neue Leseliste</h3>
            <form method="post" action="{{ route('portal.reading-lists.store') }}" class="bc-calendar-form">
                @csrf
                @include('pages.surfaces.partials.reading-list-form', ['prefix' => 'new', 'classes' => $classes])
                <x-ui.button type="submit">Leseliste anlegen</x-ui.button>
            </form>
        </section>
    @endif

    <section class="bc-content-section" aria-labelledby="class-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="class-heading">Für meine Klasse</h2>
            <span>{{ $forClass->count() }}</span>
        </div>

        @if (! $hasClass)
            <p class="bc-section-copy">Dein Ausleihkonto ist keiner Klasse zugeordnet. Deshalb gibt es hier keine Listen für eine Klasse.</p>
        @elseif ($forClass->isEmpty())
            <p class="bc-section-copy">Für deine Klasse gibt es zurzeit keine Leselisten.</p>
        @else
            <ul class="bc-wish-list">
                @foreach ($forClass as $list)
                    <li class="bc-reading-item">
                        <a href="{{ route('portal.reading-lists.show', ['listId' => $list->getKey()]) }}"><strong>{{ $list->name }}</strong></a>
                        <span>{{ $list->items_count }} Titel @if ($list->ends_on) · bis {{ $list->ends_on->format('d.m.Y') }} @endif</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-app-shell>
