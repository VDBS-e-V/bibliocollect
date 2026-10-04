@php
    use App\Modules\Patrons\Enums\PatronKind;

    $isCreate = $mode === 'create';
    $title = $isCreate ? 'Ausleihkonto anlegen' : 'Ausleihkonto bearbeiten';
    $lead = $isCreate
        ? 'Lege ein fachliches Ausleihkonto an. Ein Onlinekonto ist dafür nicht erforderlich.'
        : $patron->displayName().' · '.$patron->library_number;
    $formAction = $isCreate
        ? route('pos.patrons.store')
        : route('pos.patrons.update', ['patronId' => $patron->getKey()]);
    $currentClassId = old('school_class_id', $patron?->school_class_id);
    $currentClassIncluded = $currentClassId === null
        || $schoolClasses->contains(fn ($schoolClass): bool => (string) $schoolClass->getKey() === (string) $currentClassId);
    $kindLabel = $isCreate ? null : match ($patron->kind->value) {
        'student' => 'Schüler:in',
        'teacher' => 'Lehrkraft',
        'employee' => 'Mitarbeiter:in',
        default => $patron->kind->value,
    };
@endphp

<x-app-shell surface="pos" :title="$title">
    <x-ui.page-header
        kicker="Ausleihkonten"
        :title="$title"
        :lead="$lead"
    />

    <div class="bc-context-actions">
        <a href="{{ $isCreate ? route('pos.patrons.index') : route('pos.patrons.show', ['patronId' => $patron->getKey()]) }}">← Abbrechen</a>
    </div>

    <form method="post" action="{{ $formAction }}" class="bc-patron-form">
        @csrf
        @if (! $isCreate)
            @method('PATCH')
        @endif

        <section aria-labelledby="patron-form-basis">
            <div class="bc-section-heading"><h2 id="patron-form-basis">Stammdaten</h2></div>
            <div class="bc-patron-form__grid">
                <x-ui.input
                    label="Bibliotheksnummer"
                    name="library_number"
                    :value="old('library_number', $patron?->library_number)"
                    :error="$errors->first('library_number')"
                    autocomplete="off"
                    required
                />

                @if ($isCreate)
                    <x-ui.select
                        label="Kontotyp"
                        name="kind"
                        hint="Der Kontotyp wird nach dem Anlegen nicht beiläufig geändert, weil daran weitere Fachregeln hängen."
                        :error="$errors->first('kind')"
                        required
                    >
                        @foreach (PatronKind::cases() as $kind)
                            @php($label = match ($kind) {
                                PatronKind::Student => 'Schüler:in',
                                PatronKind::Teacher => 'Lehrkraft',
                                PatronKind::Employee => 'Mitarbeiter:in',
                            })
                            <option value="{{ $kind->value }}" @selected(old('kind', PatronKind::Student->value) === $kind->value)>{{ $label }}</option>
                        @endforeach
                    </x-ui.select>
                @else
                    <div class="bc-readonly-field">
                        <span class="bc-readonly-field__label">Kontotyp</span>
                        <div class="bc-readonly-field__value">{{ $kindLabel }}</div>
                        <p class="bc-field__hint">Typänderungen erfolgen nicht über die normale Stammdatenpflege.</p>
                    </div>
                @endif

                <x-ui.input
                    label="Vorname"
                    name="first_name"
                    :value="old('first_name', $patron?->first_name)"
                    :error="$errors->first('first_name')"
                    autocomplete="given-name"
                    required
                />

                <x-ui.input
                    label="Nachname"
                    name="last_name"
                    :value="old('last_name', $patron?->last_name)"
                    :error="$errors->first('last_name')"
                    autocomplete="family-name"
                    required
                />

                <x-ui.input
                    label="Geburtsdatum"
                    name="birth_date"
                    type="date"
                    :value="old('birth_date', $patron?->birth_date?->format('Y-m-d'))"
                    hint="Pflichtangabe für Altersfreigaben."
                    :error="$errors->first('birth_date')"
                    autocomplete="bday"
                    required
                />

                <x-ui.input
                    label="E-Mail am Ausleihkonto"
                    name="email"
                    type="email"
                    :value="old('email', $patron?->email)"
                    hint="Optional. Ein Ausleihkonto funktioniert auch ohne E-Mail und ohne Onlinekonto."
                    :error="$errors->first('email')"
                    autocomplete="email"
                />
            </div>
        </section>

        <section aria-labelledby="patron-form-school">
            <div class="bc-section-heading"><h2 id="patron-form-school">Schule & Austritt</h2></div>
            <div class="bc-patron-form__grid">
                <x-ui.select
                    label="Klasse"
                    name="school_class_id"
                    hint="Die Klassenzuordnung wird nur bei Schüler:innen gespeichert."
                    :error="$errors->first('school_class_id')"
                >
                    <option value="">Keine Zuordnung</option>
                    @if (! $currentClassIncluded && $patron?->schoolClass)
                        <option value="{{ $patron->schoolClass->getKey() }}" selected>
                            {{ $patron->schoolClass->name }} · bisherige, nicht aktive Zuordnung
                        </option>
                    @endif
                    @foreach ($schoolClasses as $schoolClass)
                        <option value="{{ $schoolClass->getKey() }}" @selected((string) $currentClassId === (string) $schoolClass->getKey())>
                            {{ $schoolClass->name }} · Jahrgang {{ $schoolClass->grade_level }} · {{ $schoolClass->schoolYear?->name }}
                        </option>
                    @endforeach
                </x-ui.select>

                <x-ui.input
                    label="Geplanter Austritt"
                    name="leaving_on"
                    type="date"
                    :value="old('leaving_on', $patron?->leaving_on?->format('Y-m-d'))"
                    hint="Optional. Der spätere Statuswechsel ‚ausgeschieden‘ bleibt ein eigener Workflow."
                    :error="$errors->first('leaving_on')"
                />
            </div>
        </section>

        <x-ui.alert title="Datensparsamkeit">
            BiblioCollect führt hier nur die für Bibliotheksbetrieb, Altersfreigaben und Schulzuordnung vorgesehenen Stammdaten. Es wird kein allgemeines Eltern- oder Sorgeberechtigtenregister angelegt.
        </x-ui.alert>

        <div class="bc-action-row">
            <x-ui.button type="submit">{{ $isCreate ? 'Ausleihkonto anlegen' : 'Änderungen speichern' }}</x-ui.button>
            <x-ui.button href="{{ $isCreate ? route('pos.patrons.index') : route('pos.patrons.show', ['patronId' => $patron->getKey()]) }}" variant="secondary">Abbrechen</x-ui.button>
        </div>
    </form>
</x-app-shell>
