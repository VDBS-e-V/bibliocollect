<x-app-shell surface="pos" title="Buchwünsche">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Buchwünsche"
        lead="Wünsche von Leser:innen. Der Stand und deine Anmerkung sind im Konto der Person sichtbar; bei Änderungen geht eine Mail an sie."
    />

    @if (session('wish_notice'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('wish_notice') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="bc-context-actions">
        <x-ui.button href="{{ route('pos.wishes.create') }}">Buchwunsch erfassen</x-ui.button>
    </div>

    <form method="get" action="{{ route('pos.wishes.index') }}" class="bc-audit-filter" role="search">
        <x-ui.select label="Anzeigen" name="status" id="wish-filter" data-auto-submit>
            <option value="offen" @selected($filter === 'offen')>Offene Wünsche</option>
            <option value="alle" @selected($filter === 'alle')>Alle</option>
            @foreach ($statuses as $case)
                <option value="{{ $case->value }}" @selected($filter === $case->value)>{{ $case->label() }}@isset($counts[$case->value]) ({{ $counts[$case->value] }})@endisset</option>
            @endforeach
        </x-ui.select>
        <x-ui.input label="Titel, Autor oder ISBN" name="q" id="wish-search" :value="$term" />
        <x-ui.button type="submit" variant="secondary">Suchen</x-ui.button>
    </form>

    <section class="bc-content-section" aria-labelledby="wishes-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="wishes-heading">Wünsche</h2>
            <span>{{ $wishes->count() }}</span>
        </div>

        @if ($wishes->isEmpty())
            <p class="bc-section-copy">Keine Wünsche gefunden.</p>
        @else
            <ul class="bc-wish-list">
                @foreach ($wishes as $wish)
                    <li class="bc-wish">
                        <div class="bc-wish__body">
                            <strong>{{ $wish->title }}</strong>
                            <span>
                                {{ $wish->author ?: 'Autor:in unbekannt' }}@if ($wish->isbn) · ISBN <span class="bc-tabular">{{ $wish->isbn }}</span>@endif
                            </span>
                            <span>
                                {{ $wish->patron ? $wish->patron->last_name.', '.$wish->patron->first_name.($wish->patron->schoolClass ? ' ('.$wish->patron->schoolClass->name.')' : '') : ($wish->contact_name || $wish->contact_email ? trim(($wish->contact_name ?: 'Unbekannt').' '.($wish->contact_email ? '<'.$wish->contact_email.'>' : '')) : 'ohne Person erfasst') }}
                                · {{ $wish->created_at?->format('d.m.Y') }}
                            </span>
                            @if ($wish->note)
                                <span>Anmerkung: {{ $wish->note }}</span>
                            @endif
                            @if (($similar[(string) $wish->getKey()] ?? 0) > 0)
                                <x-ui.badge variant="warning">{{ $similar[(string) $wish->getKey()] }} weitere{{ $similar[(string) $wish->getKey()] === 1 ? 'r' : '' }} Wunsch für diesen Titel</x-ui.badge>
                            @endif
                            <span>
                                <a href="{{ route('pos.catalog.index', ['q' => $wish->isbn ?: $wish->title]) }}">Im Katalog suchen</a>
                                · <a href="{{ route('pos.catalog.intake.identify', ['neu' => 1]) }}">Medium erfassen</a>
                            </span>
                        </div>
                        <form method="post" action="{{ route('pos.wishes.update', array_filter(['wishId' => $wish->getKey(), 'status' => request('status'), 'q' => request('q')])) }}" class="bc-wish__form">
                            @csrf
                            @method('PATCH')
                            <x-ui.select label="Stand" name="status" id="status-{{ $wish->getKey() }}">
                                @foreach ($statuses as $case)
                                    @if ($case->isDecision())
                                        <option value="{{ $case->value }}" @selected($wish->status === $case)>{{ $case->label() }}</option>
                                    @endif
                                @endforeach
                                @if ($wish->status === \App\Modules\Circulation\Enums\WishStatus::Withdrawn)
                                    <option value="withdrawn" selected disabled>Zurückgezogen</option>
                                @endif
                            </x-ui.select>
                            <x-ui.input label="Antwort an die Person (optional)" name="answer" id="answer-{{ $wish->getKey() }}" :value="$wish->answer" maxlength="500" />
                            <x-ui.button type="submit" variant="secondary">Speichern</x-ui.button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

</x-app-shell>
