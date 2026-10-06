@php
    $dayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];
@endphp

<x-app-shell surface="administration" title="Öffnungszeiten und Schließtage">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Öffnungszeiten und Schließtage"
        lead="Fälligkeiten fallen nie auf einen Schließtag: Die Bibliothek verschiebt sie auf den nächsten Öffnungstag."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
    </div>

    @if (session('school_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('school_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="opening-hours-heading">
        <div class="bc-section-heading"><h2 id="opening-hours-heading">Regelmäßige Öffnungszeiten</h2></div>

        <form method="post" action="{{ route('administration.calendar.hours') }}" class="bc-calendar-form">
            @csrf
            @method('PUT')
            <table class="bc-calendar-table">
                <thead>
                    <tr>
                        <th scope="col">Wochentag</th>
                        <th scope="col">Geöffnet</th>
                        <th scope="col">Von</th>
                        <th scope="col">Bis</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dayNames as $number => $name)
                        @php
                            $hour = $hours->get($number);
                            $isOpen = old('days.'.$number.'.is_open', $hour?->is_open ?? false);
                            $opens = old('days.'.$number.'.opens_at', $hour?->opens_at ? substr((string) $hour->opens_at, 0, 5) : '');
                            $closes = old('days.'.$number.'.closes_at', $hour?->closes_at ? substr((string) $hour->closes_at, 0, 5) : '');
                        @endphp
                        <tr>
                            <th scope="row">{{ $name }}</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="days[{{ $number }}][is_open]" value="1" @checked($isOpen)>
                                    <span class="bc-visually-hidden">{{ $name }} geöffnet</span>
                                </label>
                            </td>
                            <td><input type="time" name="days[{{ $number }}][opens_at]" value="{{ $opens }}" aria-label="{{ $name }} von"></td>
                            <td><input type="time" name="days[{{ $number }}][closes_at]" value="{{ $closes }}" aria-label="{{ $name }} bis"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <x-ui.button type="submit">Öffnungszeiten speichern</x-ui.button>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="closures-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="closures-heading">Schließtage</h2>
            <span>{{ $closures->count() }}</span>
        </div>
        <p class="bc-section-copy">Ferien, Studientage und andere Tage ohne Betrieb. Bereits vergebene Fälligkeiten ändern sich dadurch nicht; neue Ausleihen und Verlängerungen berücksichtigen die Schließtage.</p>

        <form method="post" action="{{ route('administration.calendar.closures.store') }}" class="bc-calendar-closure-form">
            @csrf
            <x-ui.input label="Von" name="from" type="date" :value="old('from')" :error="$errors->first('from') ?: null" />
            <x-ui.input label="Bis (optional)" name="to" type="date" :value="old('to')" :error="$errors->first('to') ?: null" />
            <x-ui.input label="Grund (optional)" name="reason" :value="old('reason')" placeholder="z. B. Herbstferien" />
            <div class="bc-school-form-action"><x-ui.button type="submit" variant="secondary">Schließtage eintragen</x-ui.button></div>
        </form>

        <p class="bc-section-copy">
            @if ($showPast)
                <a href="{{ route('administration.calendar.index') }}">Nur kommende Schließtage zeigen</a>
            @else
                <a href="{{ route('administration.calendar.index', ['vergangene' => 1]) }}">Auch vergangene Schließtage zeigen</a>
            @endif
        </p>

        @if ($closures->isEmpty())
            <p class="bc-section-copy">Keine Schließtage eingetragen.</p>
        @else
            <table class="bc-calendar-table">
                <thead>
                    <tr>
                        <th scope="col">Datum</th>
                        <th scope="col">Grund</th>
                        <th scope="col"><span class="bc-visually-hidden">Aktion</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($closures as $closure)
                        <tr>
                            <th scope="row" class="bc-tabular">{{ $closure->date->isoFormat('dd, DD.MM.YYYY') }}</th>
                            <td>{{ $closure->reason ?: '—' }}</td>
                            <td>
                                <form method="post" action="{{ route('administration.calendar.closures.destroy', ['closureId' => $closure->getKey()]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="secondary">Entfernen</x-ui.button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</x-app-shell>
