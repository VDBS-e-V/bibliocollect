@php($list = $list ?? null)
<div class="bc-reading-fields">
    <x-ui.input label="Name der Liste *" name="name" id="{{ $prefix }}-name" :value="old('name', $list?->name)" required maxlength="120" hint="Zum Beispiel „Klassenlektüre 7a“ oder „Bücher zum Thema Weltraum“." />
    <x-ui.input label="Beschreibung" name="description" id="{{ $prefix }}-description" :value="old('description', $list?->description)" maxlength="500" hint="Ein bis zwei Sätze für die Klasse (freiwillig)." />
    <x-ui.input label="Läuft bis" name="ends_on" id="{{ $prefix }}-ends" type="date" :value="old('ends_on', $list?->ends_on?->toDateString())" hint="Danach ist die Liste weder über den Link noch im Konto erreichbar (freiwillig)." />
</div>

@php($chosen = array_map('strval', old('school_class_ids', $list?->classes->pluck('id')->all() ?? [])))
@php($classNames = collect($classes)->pluck('name', 'id'))
<div class="bc-class-picker" data-class-picker>
    <label class="bc-field__label" for="{{ $prefix }}-class-select">Klassen</label>
    <p class="bc-field__hint">Schüler:innen der gewählten Klassen sehen die Liste in ihrem Konto. Das ist freiwillig: Mit dem Link kann jede Person die Liste ohne Konto öffnen.</p>
    <select id="{{ $prefix }}-class-select" class="bc-field__control" data-class-picker-select>
        <option value="">Klasse hinzufügen …</option>
        @foreach ($classes as $class)
            <option value="{{ $class['id'] }}" @disabled(in_array($class['id'], $chosen, true))>{{ $class['name'] }}</option>
        @endforeach
    </select>
    <ul class="bc-class-picker__list" data-class-picker-list aria-label="Gewählte Klassen">
        @foreach ($chosen as $classId)
            @if ($classNames->has($classId))
                <li class="bc-class-picker__item" data-class-id="{{ $classId }}">
                    <span>{{ $classNames[$classId] }}</span>
                    <input type="hidden" name="school_class_ids[]" value="{{ $classId }}">
                    <button type="button" class="bc-class-picker__remove" data-class-remove aria-label="Klasse {{ $classNames[$classId] }} entfernen">×</button>
                </li>
            @endif
        @endforeach
    </ul>
    <p class="bc-section-copy" data-class-picker-empty @if ($chosen !== []) hidden @endif>Noch keine Klasse gewählt. Die Liste ist dann nur über den Link erreichbar.</p>
</div>

<label class="bc-public-catalog-filter__check">
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $list?->is_published ?? true))>
    <span>
        <strong>Liste ist aktiv</strong>
        <small>Ausgeschaltet sind der öffentliche Link und die Anzeige im Konto der Klassen nicht erreichbar.</small>
    </span>
</label>
