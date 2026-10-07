<x-app-shell surface="pos" title="Aussondern bestätigen">
    <x-ui.page-header
        kicker="Bücher aussondern"
        title="Aussondern bestätigen"
        :lead="count($ready).' '.(count($ready) === 1 ? 'Buch kann' : 'Bücher können').' ausgesondert werden. Prüfe die Liste, wähle Grund und Verbleib und bestätige.'"
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.withdrawal') }}">← Zurück zur Eingabe</a>
    </div>

    @if ($errors->any())
        <x-ui.alert variant="error" title="Bitte prüfen">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if ($blocked !== [] || $already !== [] || $unknown !== [])
        <x-ui.alert title="Diese bleiben im Bestand">
            @foreach ($blocked as $row)<p>{{ $row['copy']->barcode }} „{{ $row['copy']->edition->title->preferred_title }}“ {{ implode(' und ', $row['problems']) }}.</p>@endforeach
            @foreach ($already as $copy)<p>{{ $copy->barcode }} „{{ $copy->edition->title->preferred_title }}“ ist schon ausgesondert.</p>@endforeach
            @if ($unknown !== [])<p>Unbekannte Nummern: {{ implode(', ', $unknown) }}.</p>@endif
        </x-ui.alert>
    @endif

    <form method="post" action="{{ route('pos.withdrawal.store') }}" class="bc-intake-form">
        @csrf

        <section class="bc-content-section" aria-labelledby="ready-heading">
            <div class="bc-section-heading bc-section-heading--with-meta"><h2 id="ready-heading">Diese Bücher werden ausgesondert</h2><span>{{ count($ready) }}</span></div>
            <table class="bc-calendar-table">
                <thead><tr><th scope="col">Inventarnummer</th><th scope="col">Titel</th><th scope="col">Standort</th></tr></thead>
                <tbody>
                    @foreach ($ready as $copy)
                        <tr>
                            <th scope="row" class="bc-tabular">{{ $copy->barcode }}<input type="hidden" name="copies[]" value="{{ $copy->getKey() }}"></th>
                            <td>{{ $copy->edition->title->preferred_title }}</td>
                            <td>{{ $copy->shelf_location ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <fieldset class="bc-intake-fieldset">
            <legend>2. Grund und Verbleib</legend>
            <x-ui.select label="Grund" name="reason" id="withdraw-reason" :error="$errors->first('reason')">
                @foreach ($reasons as $case)
                    <option value="{{ $case->value }}" @selected(old('reason') === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select label="Was geschieht mit den Büchern?" name="fate" id="withdraw-fate" :error="$errors->first('fate')">
                @foreach ($fates as $case)
                    <option value="{{ $case->value }}" @selected(old('fate') === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.input label="Datum der Aussonderung" name="date" id="withdraw-date" type="date" :value="old('date', $today)" :max="$today" :error="$errors->first('date')" />
        </fieldset>

        <div class="bc-intake-actions">
            <a href="{{ route('pos.withdrawal') }}">Abbrechen</a>
            <x-ui.button type="submit">{{ count($ready) }} {{ count($ready) === 1 ? 'Buch' : 'Bücher' }} aussondern</x-ui.button>
        </div>
    </form>
</x-app-shell>
