<x-app-shell surface="administration" title="Schule und Schuljahre">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Schule und Schuljahre"
        lead="Schuljahre und Klassen vorbereiten, aktiv schalten und den nächsten Schuljahreswechsel prüfen."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
        <a href="{{ route('administration.transition.show') }}">Schuljahreswechsel vorbereiten</a>
    </div>

    @if (session('school_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('school_success') }}</x-ui.alert>
    @endif

    @if (session('school_error'))
        <x-ui.alert variant="error" title="Fehler">{{ session('school_error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">
            Bitte prüfe die markierten Eingaben und speichere den betreffenden Abschnitt erneut.
        </x-ui.alert>
    @endif

    <div class="bc-school-layout">
        <div class="bc-school-layout__main">
            <section class="bc-content-section" aria-labelledby="create-school-year-heading">
                <div class="bc-section-heading"><h2 id="create-school-year-heading">Schuljahr vorbereiten</h2></div>
                <p class="bc-section-copy">Neue Schuljahre werden zunächst als Entwurf angelegt. Klassen können vollständig vorbereitet werden, bevor das Jahr aktiv geschaltet wird.</p>

                <form method="post" action="{{ route('administration.school-years.store') }}" class="bc-school-year-create-form">
                    @csrf
                    <x-ui.input
                        label="Bezeichnung"
                        name="name"
                        :value="old('name')"
                        placeholder="z. B. 2027/28"
                        :error="$errors->first('name') ?: null"
                    />
                    <x-ui.input
                        label="Beginn"
                        name="starts_on"
                        type="date"
                        :value="old('starts_on')"
                        :error="$errors->first('starts_on') ?: null"
                    />
                    <x-ui.input
                        label="Ende"
                        name="ends_on"
                        type="date"
                        :value="old('ends_on')"
                        :error="$errors->first('ends_on') ?: null"
                    />
                    <div class="bc-school-form-action"><x-ui.button type="submit">Schuljahr anlegen</x-ui.button></div>
                </form>
            </section>

            <section class="bc-content-section" aria-labelledby="school-years-heading">
                <div class="bc-section-heading bc-section-heading--with-meta">
                    <h2 id="school-years-heading">Schuljahre und Klassen</h2>
                    <span>{{ $schoolYears->count() }} Schuljahre</span>
                </div>

                @forelse ($schoolYears as $schoolYear)
                    <article class="bc-school-year-card" id="school-year-{{ $schoolYear->getKey() }}">
                        <header class="bc-school-year-card__header">
                            <div>
                                <div class="bc-status-stack">
                                    <x-ui.badge :variant="$schoolYear->is_active ? 'success' : 'neutral'">
                                        {{ $schoolYear->is_active ? 'Aktiv' : 'Entwurf' }}
                                    </x-ui.badge>
                                    <span class="bc-status-text">{{ $schoolYear->classes->where('is_active', true)->count() }} aktive Klassen</span>
                                </div>
                                <h3>{{ $schoolYear->name }}</h3>
                                <p>{{ $schoolYear->starts_on->format('d.m.Y') }} – {{ $schoolYear->ends_on->format('d.m.Y') }}</p>
                            </div>
                        </header>

                        <form method="post" action="{{ route('administration.school-years.update', ['schoolYearId' => $schoolYear->getKey()]) }}" class="bc-school-year-form">
                            @csrf
                            @method('PATCH')
                            <x-ui.input label="Bezeichnung" name="name" :id="'year-'.$schoolYear->getKey().'-name'" :value="$schoolYear->name" />
                            <x-ui.input label="Beginn" name="starts_on" type="date" :id="'year-'.$schoolYear->getKey().'-starts_on'" :value="$schoolYear->starts_on->toDateString()" />
                            <x-ui.input label="Ende" name="ends_on" type="date" :id="'year-'.$schoolYear->getKey().'-ends_on'" :value="$schoolYear->ends_on->toDateString()" />
                            <div class="bc-school-form-action"><x-ui.button type="submit" variant="secondary">Schuljahr speichern</x-ui.button></div>
                        </form>

                        <div class="bc-school-classes">
                            <div class="bc-school-classes__heading">
                                <strong>Klassen</strong>
                                <span>Jahrgang 1–13 · inaktive Klassen bleiben historisch erhalten</span>
                            </div>

                            @forelse ($schoolYear->classes as $schoolClass)
                                <form method="post" action="{{ route('administration.school-classes.update', ['schoolClassId' => $schoolClass->getKey()]) }}" class="bc-school-class-row">
                                    @csrf
                                    @method('PATCH')
                                    <x-ui.input label="Klasse" name="name" :id="'class-'.$schoolClass->getKey().'-name'" :value="$schoolClass->name" />
                                    <x-ui.input label="Jahrgang" name="grade_level" type="number" min="1" max="13" :id="'class-'.$schoolClass->getKey().'-grade'" :value="$schoolClass->grade_level" />
                                    <x-ui.input label="Klassenleitung" name="homeroom_teacher" :id="'class-'.$schoolClass->getKey().'-homeroom'" :value="$schoolClass->homeroom_teacher" />
                                    <label class="bc-school-active-control">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked($schoolClass->is_active)>
                                        <span>für Zuordnungen aktiv</span>
                                    </label>
                                    <div class="bc-school-form-action"><x-ui.button type="submit" variant="secondary">Speichern</x-ui.button></div>
                                </form>
                            @empty
                                <p class="bc-school-empty">Noch keine Klassen angelegt.</p>
                            @endforelse

                            <form method="post" action="{{ route('administration.school-classes.store-standard', ['schoolYearId' => $schoolYear->getKey()]) }}" class="bc-school-class-row">
                                @csrf
                                <p class="bc-section-copy">Alle Klassen der Schule auf einmal anlegen: Grundschule 1.1 bis 6.3, Mittelstufe 7.1 bis 10.5 mit 9.6, 10.6 und WiKo, Oberstufe 11.1 bis 11.4, 12 und 13. Vorhandene Klassen bleiben unverändert.</p>
                                <div class="bc-school-form-action"><x-ui.button type="submit" variant="secondary">Alle Klassen der Schule anlegen</x-ui.button></div>
                            </form>

                            <form method="post" action="{{ route('administration.school-classes.store', ['schoolYearId' => $schoolYear->getKey()]) }}" class="bc-school-class-row bc-school-class-row--new">
                                @csrf
                                <x-ui.input label="Neue Klasse" name="name" :id="'new-class-'.$schoolYear->getKey().'-name'" placeholder="z. B. 8a" />
                                <x-ui.input label="Jahrgang" name="grade_level" type="number" min="1" max="13" :id="'new-class-'.$schoolYear->getKey().'-grade'" />
                                <x-ui.input label="Klassenleitung" name="homeroom_teacher" :id="'new-class-'.$schoolYear->getKey().'-homeroom'" />
                                <label class="bc-school-active-control">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" checked>
                                    <span>für Zuordnungen aktiv</span>
                                </label>
                                <div class="bc-school-form-action"><x-ui.button type="submit">Klasse anlegen</x-ui.button></div>
                            </form>
                        </div>
                    </article>
                @empty
                    <x-ui.alert title="Noch keine Schuljahre">Lege zuerst ein Schuljahr an und ergänze anschließend die Klassen.</x-ui.alert>
                @endforelse
            </section>
        </div>

        <aside class="bc-school-layout__aside">
            <section class="bc-side-panel" aria-labelledby="transition-heading">
                <h2 id="transition-heading">Schuljahreswechsel vorbereiten</h2>
                <p>Hier wird geprüft, ob für die bestehenden Jahrgänge des aktiven Schuljahres Folgejahrgänge im Zieljahr vorhanden sind. Neue 1. Klassen und individuelle Klassenwechsel werden nicht automatisch abgeleitet.</p>

                @if ($transition->candidateYears->isEmpty())
                    <x-ui.alert title="Kein Zieljahr">Lege zunächst ein weiteres Schuljahr als Entwurf an.</x-ui.alert>
                @else
                    <form method="get" action="{{ route('administration.school.index') }}" class="bc-transition-target-form">
                        <label class="bc-field__label" for="transition-target">Zieljahr</label>
                        <select id="transition-target" name="target" class="bc-field__control" onchange="this.form.submit()">
                            @foreach ($transition->candidateYears as $candidateYear)
                                <option value="{{ $candidateYear->getKey() }}" @selected($transition->targetYear?->getKey() === $candidateYear->getKey())>
                                    {{ $candidateYear->name }}
                                </option>
                            @endforeach
                        </select>
                        <noscript><x-ui.button type="submit" variant="secondary">Prüfen</x-ui.button></noscript>
                    </form>

                    <dl class="bc-side-definition-list">
                        <div><dt>Aktuelles Jahr</dt><dd>{{ $transition->activeYear?->name ?? 'noch keines' }}</dd></div>
                        <div><dt>Zieljahr</dt><dd>{{ $transition->targetYear?->name ?? '—' }}</dd></div>
                        <div><dt>Aktive Zielklassen</dt><dd>{{ $transition->targetActiveClassCount }}</dd></div>
                    </dl>

                    @if ($transition->isReady())
                        <x-ui.alert variant="success" title="Vorbereitung vollständig">Für alle aus den aktuellen Klassen ableitbaren Folgejahrgänge ist mindestens eine aktive Zielklasse vorhanden.</x-ui.alert>
                    @elseif ($transition->missingPromotedGradeLevels !== [])
                        <x-ui.alert variant="error" title="Zielklassen fehlen">
                            Noch nicht vorbereitet: Jahrgang {{ implode(', ', $transition->missingPromotedGradeLevels) }}.
                        </x-ui.alert>
                    @else
                        <x-ui.alert title="Noch nicht bereit">Das Zieljahr braucht mindestens eine aktive Klasse.</x-ui.alert>
                    @endif

                    @if ($transition->targetYear !== null)
                        <div class="bc-transition-activate">
                            <p><strong>Wichtig:</strong> Das Aktivieren schaltet nur das Schuljahr für neue Klassenzuordnungen um. Bestehende Schüler:innen bleiben zunächst ihrer bisherigen Klasse zugeordnet; ein späterer Massenwechsel erhält dafür einen eigenen Vorschau- und Konfliktworkflow.</p>
                            <form method="post" action="{{ route('administration.school-years.activate', ['schoolYearId' => $transition->targetYear->getKey()]) }}" class="bc-departure-workflow">
                                @csrf
                                <label class="bc-departure-confirm">
                                    <input type="checkbox" name="confirm_activation" value="1" required @disabled(! $transition->isReady())>
                                    <span>Ich bestätige, dass das Zieljahr für neue Klassenzuordnungen aktiv geschaltet werden soll.</span>
                                </label>
                                <x-ui.button type="submit" :disabled="! $transition->isReady()">Zieljahr aktiv schalten</x-ui.button>
                            </form>
                        </div>
                    @endif
                @endif
            </section>
        </aside>
    </div>
</x-app-shell>
