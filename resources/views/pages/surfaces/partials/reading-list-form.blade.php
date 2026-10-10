@php($list = $list ?? null)
<div class="bc-reading-fields">
    <x-ui.input label="Name der Liste *" name="name" id="{{ $prefix }}-name" :value="old('name', $list?->name)" required maxlength="120" hint="Zum Beispiel „Klassenlektüre 7a“ oder „Bücher zum Thema Weltraum“." />
    <x-ui.input label="Beschreibung" name="description" id="{{ $prefix }}-description" :value="old('description', $list?->description)" maxlength="500" hint="Ein bis zwei Sätze für die Klasse (freiwillig)." />
    <x-ui.input label="Läuft bis" name="ends_on" id="{{ $prefix }}-ends" type="date" :value="old('ends_on', $list?->ends_on?->toDateString())" hint="Danach ist die Liste weder über den Link noch im Konto erreichbar (freiwillig)." />
</div>

@php($chosen = array_map('strval', old('school_class_ids', $list?->classes->pluck('id')->all() ?? [])))
<fieldset class="bc-reading-classes">
    <legend>Klassen</legend>
    <p class="bc-field__hint">Schüler:innen dieser Klassen sehen die Liste in ihrem Konto. Das ist freiwillig: Mit dem Link unten kann jede Person die Liste ohne Konto öffnen.</p>
    @forelse ($classes as $class)
        <label class="bc-public-catalog-filter__check">
            <input type="checkbox" name="school_class_ids[]" value="{{ $class['id'] }}" @checked(in_array($class['id'], $chosen, true))>
            <span>{{ $class['name'] }}</span>
        </label>
    @empty
        <p class="bc-section-copy">Es sind noch keine Klassen angelegt.</p>
    @endforelse
</fieldset>

<label class="bc-public-catalog-filter__check">
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $list?->is_published ?? true))>
    <span>
        <strong>Liste ist aktiv</strong>
        <small>Ausgeschaltet sind der öffentliche Link und die Anzeige im Konto der Klassen nicht erreichbar.</small>
    </span>
</label>
