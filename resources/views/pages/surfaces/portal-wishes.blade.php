<x-app-shell surface="portal" title="Buchwünsche">
    <x-ui.page-header
        kicker="Mein Konto"
        title="Buchwünsche"
        lead="Dir fehlt ein Buch in der Bibliothek? Wünsch es dir. Du siehst hier, was daraus wird."
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
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if (! $patron)
        <x-ui.alert title="Noch nicht verknüpft">
            Dein Onlinekonto ist noch nicht mit einem Ausleihkonto verknüpft. Frag in der Bibliothek nach einem Verknüpfungscode, dann kannst du dir Bücher wünschen.
        </x-ui.alert>
    @else
        <section class="bc-content-section" aria-labelledby="wish-form-heading">
            <div class="bc-section-heading"><h2 id="wish-form-heading">Neuer Wunsch</h2></div>
            <p class="bc-section-copy">Du kannst höchstens {{ $maxOpen }} offene Wünsche gleichzeitig haben. Ob ein Wunsch erfüllt wird, entscheidet die Bibliothek.</p>
            <x-ui.button href="{{ route('public.wishes.create') }}">Buchwunsch erfassen</x-ui.button>
        </section>

        <section class="bc-content-section" aria-labelledby="wish-list-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="wish-list-heading">Meine Wünsche</h2>
                <span>{{ $wishes->count() }}</span>
            </div>

            @if ($wishes->isEmpty())
                <p class="bc-section-copy">Du hast noch keinen Wunsch abgegeben.</p>
            @else
                <ul class="bc-wish-list">
                    @foreach ($wishes as $wish)
                        <li class="bc-wish">
                            <div class="bc-wish__body">
                                <strong>{{ $wish->title }}</strong>
                                <span>{{ $wish->author ?: 'Autor:in unbekannt' }} · abgegeben am {{ $wish->created_at?->format('d.m.Y') }}</span>
                                <span>
                                    <x-ui.badge :variant="$wish->status->value === 'fulfilled' ? 'success' : ($wish->status->value === 'declined' ? 'danger' : 'neutral')">{{ $wish->status->label() }}</x-ui.badge>
                                    @if ($wish->answer) Anmerkung der Bibliothek: {{ $wish->answer }} @endif
                                </span>
                            </div>
                            @if ($wish->status->isOpen())
                                <form method="post" action="{{ route('portal.wishes.withdraw', ['wishId' => $wish->getKey()]) }}">
                                    @csrf
                                    <button type="submit" class="bc-intake-linkbutton" aria-label="Wunsch {{ $wish->title }} zurückziehen">Zurückziehen</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif
</x-app-shell>
