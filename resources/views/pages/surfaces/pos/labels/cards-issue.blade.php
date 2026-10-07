@php
    $firstMissing = null;

    foreach ($patrons as $candidate) {
        if (! isset($cards[(string) $candidate->getKey()])) {
            $firstMissing = (string) $candidate->getKey();
            break;
        }
    }
@endphp

<x-app-shell surface="pos" title="Ausweise ausgeben">
    <x-ui.page-header
        kicker="Ausleihkonten"
        title="Ausweise ausgeben"
        lead="Klasse wählen, dann den Ausweis neben dem Namen scannen. Danach springt der Cursor zur nächsten Person ohne Ausweis. Dieselbe Seite zeigt, wer noch keinen Ausweis hat."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.labels.cards') }}">← Zurück zu den Ausweisen</a>
        @if ($classId !== '')
            <button type="button" class="bc-intake-linkbutton" data-print>Liste drucken</button>
        @endif
    </div>

    @if (session('issue_notice'))
        <x-ui.alert variant="success" title="Zugeordnet">{{ session('issue_notice') }}</x-ui.alert>
    @endif

    @if (session('issue_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('issue_error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="get" action="{{ route('pos.labels.cards.issue') }}" class="bc-audit-filter" role="search">
        <x-ui.select label="Klasse" name="klasse" id="issue-class" data-auto-submit>
            <option value="">Bitte wählen …</option>
            <option value="alle" @selected($classId === 'alle')>Alle Klassen</option>
            @foreach ($classes as $class)
                <option value="{{ $class->getKey() }}" @selected($classId === (string) $class->getKey())>{{ $class->name }}</option>
            @endforeach
            <option value="ohne" @selected($classId === 'ohne')>Ohne Klasse (Lehrkräfte, Mitarbeitende)</option>
        </x-ui.select>
        <label class="bc-checkbox-line"><input type="checkbox" name="nur_ohne" value="1" @checked($onlyMissing) data-auto-submit> Nur Personen ohne Ausweis</label>
        <noscript><x-ui.button type="submit" variant="secondary">Anzeigen</x-ui.button></noscript>
    </form>

    @if ($classId !== '')
        <section class="bc-content-section" aria-labelledby="issue-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="issue-heading">Personen</h2>
                <span>{{ $missing }} ohne Ausweis</span>
            </div>

            @if ($patrons->isEmpty())
                <p class="bc-section-copy">{{ $onlyMissing ? 'Alle Personen haben einen Ausweis.' : 'Keine aktiven Ausleihkonten gefunden.' }}</p>
            @else
                <table class="bc-calendar-table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            @if ($classId === 'alle')
                                <th scope="col">Klasse</th>
                            @endif
                            <th scope="col">Bibliotheksnummer</th>
                            <th scope="col">Ausweis</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($patrons as $patron)
                            @php
                                $number = $cards[(string) $patron->getKey()] ?? null;
                            @endphp
                            <tr>
                                <th scope="row">{{ $patron->last_name }}, {{ $patron->first_name }}</th>
                                @if ($classId === 'alle')
                                    <td>{{ $patron->schoolClass?->name ?? '—' }}</td>
                                @endif
                                <td class="bc-tabular">{{ $patron->library_number }}</td>
                                <td>
                                    @if ($number)
                                        <span class="bc-tabular">{{ $number }}</span>
                                    @else
                                        <form method="post" action="{{ route('pos.labels.cards.issue.store') }}" class="bc-issue-scan">
                                            @csrf
                                            <input type="hidden" name="patron_id" value="{{ $patron->getKey() }}">
                                            <input type="hidden" name="klasse" value="{{ $classId }}">
                                            @if ($onlyMissing)
                                                <input type="hidden" name="nur_ohne" value="1">
                                            @endif
                                            <label class="bc-visually-hidden" for="scan-{{ $patron->getKey() }}">Ausweis für {{ $patron->first_name }} {{ $patron->last_name }} scannen</label>
                                            <input type="text" id="scan-{{ $patron->getKey() }}" name="code" autocomplete="off" placeholder="Ausweis scannen" @if ($firstMissing === (string) $patron->getKey()) autofocus @endif>
                                            <button type="submit" class="bc-intake-linkbutton">Zuordnen</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($patrons->count() >= 400)
                    <p class="bc-section-copy">Es werden höchstens 400 Personen gezeigt. Bitte eine Klasse wählen.</p>
                @endif
            @endif
        </section>
    @endif
</x-app-shell>
