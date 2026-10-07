@php
    $selectedFor = static fn ($signature): array => $signature->topics->map(fn ($topic): string => (string) $topic->getKey())->all();
@endphp

<x-app-shell surface="administration" title="Signaturen">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Signaturen und Themenbereiche"
        lead="Eine Signatur fasst Themenbereiche zu einer Regalgruppe zusammen (zum Beispiel „I. A 1 d“). Bücher bekommen eine Signatur, daraus schlägt das System beim Einsortieren das Regalbrett vor."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
        <a href="{{ route('administration.topics.index') }}">Themenbereiche bearbeiten</a>
        <a href="{{ route('administration.shelves.index') }}">Regalbretter</a>
    </div>

    @if (session('taxonomy_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('taxonomy_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="new-signature-heading">
        <div class="bc-section-heading"><h2 id="new-signature-heading">Signatur hinzufügen</h2></div>
        <form method="post" action="{{ route('administration.signatures.store') }}" class="bc-intake-form">
            @csrf
            <x-ui.input label="Signatur" name="signature" id="new-signature" :value="old('signature')" maxlength="60" required />
            <details>
                <summary>Themenbereiche wählen</summary>
                @include('pages.surfaces.administration.signatures._topic-checklist', ['topicGroups' => $topicGroups, 'selected' => old('topics', [])])
            </details>
            <x-ui.button type="submit">Hinzufügen</x-ui.button>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="signatures-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="signatures-heading">Alle Signaturen</h2>
            <span>{{ $signatures->count() }}</span>
        </div>

        @if ($signatures->isEmpty())
            <p class="bc-section-copy">Noch keine Signatur angelegt.</p>
        @else
            <ul class="bc-shelf-list">
                @foreach ($signatures as $signature)
                    <li class="bc-shelf">
                        <form method="post" action="{{ route('administration.signatures.update', ['signatureId' => $signature->getKey()]) }}" class="bc-shelf__form">
                            @csrf
                            @method('PATCH')
                            <x-ui.input label="Signatur" name="signature" id="signature-{{ $signature->getKey() }}" :value="$signature->signature" maxlength="60" required />
                            <details>
                                <summary>Themenbereiche: {{ $signature->topics->pluck('name')->implode(', ') ?: 'keine' }}</summary>
                                @include('pages.surfaces.administration.signatures._topic-checklist', ['topicGroups' => $topicGroups, 'selected' => $selectedFor($signature)])
                            </details>
                            <x-ui.button type="submit" variant="secondary">Speichern</x-ui.button>
                        </form>
                        <div class="bc-shelf__meta">
                            <span>{{ $signature->copies_count }} {{ $signature->copies_count === 1 ? 'Exemplar' : 'Exemplare' }}</span>
                            <span>Regalbrett: {{ $shelves[(string) $signature->getKey()] ?? 'keines verbunden' }}</span>
                            @if ($signature->copies_count === 0)
                                <form method="post" action="{{ route('administration.signatures.destroy', ['signatureId' => $signature->getKey()]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bc-intake-linkbutton" data-confirm="Signatur „{{ $signature->signature }}“ wirklich löschen?">Löschen</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-app-shell>
