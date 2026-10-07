<x-app-shell surface="administration" title="Regeln">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Regeln der Bibliothek"
        lead="Leihfristen, Höchstzahlen, Vormerken und Erinnerungen. Änderungen gelten sofort für neue Vorgänge. Bereits laufende Ausleihen behalten ihr Fälligkeitsdatum."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
    </div>

    @if (session('rules_success'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('rules_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Bitte prüfen">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="post" action="{{ route('administration.rules.update') }}" class="bc-catalog-form">
        @csrf
        @method('PUT')

        @foreach ($groups as $group)
            <section class="bc-content-section" aria-labelledby="rules-group-{{ $loop->index }}">
                <div class="bc-section-heading"><h2 id="rules-group-{{ $loop->index }}">{{ $group['title'] }}</h2></div>
                @if ($group['lead'] !== '')
                    <p class="bc-section-copy">{{ $group['lead'] }}</p>
                @endif

                <div class="bc-intake-fieldset__grid">
                    @foreach ($group['items'] as $item)
                        @php
                            $field = $item['field'];
                            $current = old($field, $values[$field]);
                            $default = $defaults[$field];
                            $hint = trim($item['hint'].' Standard: '.($item['type'] === 'bool' ? ($default ? 'ja' : 'nein') : ($default === null ? 'leer' : $default)).'.');
                        @endphp

                        @if ($item['type'] === 'bool')
                            <div class="bc-field">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <label class="bc-public-catalog-filter__check" for="{{ $field }}">
                                    <input id="{{ $field }}" name="{{ $field }}" type="checkbox" value="1" @checked((bool) $current)>
                                    <span><strong>{{ $item['label'] }}</strong></span>
                                </label>
                                <p class="bc-field__hint">{{ $hint }}</p>
                            </div>
                        @else
                            <x-ui.input
                                :label="$item['label']"
                                :name="$field"
                                :id="$field"
                                type="number"
                                :value="$current"
                                :hint="$hint"
                                :error="$errors->first($field)"
                                :min="$item['min']"
                                :max="$item['max']"
                                :required="! $item['nullable']"
                            />
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="bc-intake-actions">
            <x-ui.button type="submit">Regeln speichern</x-ui.button>
        </div>
    </form>

    <section class="bc-content-section" aria-labelledby="rules-reset-heading">
        <div class="bc-section-heading"><h2 id="rules-reset-heading">Auf Standard zurücksetzen</h2></div>
        <p class="bc-section-copy">Verwirft alle hier gespeicherten Änderungen. Danach gelten wieder die Standardwerte der Anwendung.</p>
        <form method="post" action="{{ route('administration.rules.reset') }}">
            @csrf
            <x-ui.button type="submit" variant="secondary" data-confirm="Alle Regeln auf die Standardwerte zurücksetzen?">Alle Regeln zurücksetzen</x-ui.button>
        </form>
    </section>
</x-app-shell>
