@php($list = $list ?? null)
<div class="bc-reading-fields">
    <x-ui.input label="Name der Liste *" name="name" id="{{ $prefix }}-name" :value="old('name', $list?->name)" required maxlength="120" hint="Zum Beispiel „Klassenlektüre 7a“ oder „Bücher zum Thema Weltraum“." />
    <x-ui.select label="Klasse" name="school_class_id" id="{{ $prefix }}-class" hint="Die Schüler:innen dieser Klasse sehen die Liste in ihrem Konto. Ohne Klasse bleibt sie nur für dich sichtbar.">
        <option value="">Keine Klasse (nur für mich)</option>
        @foreach ($classes as $class)
            <option value="{{ $class['id'] }}" @selected(old('school_class_id', $list?->school_class_id) === $class['id'])>{{ $class['name'] }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input label="Beschreibung" name="description" id="{{ $prefix }}-description" :value="old('description', $list?->description)" maxlength="500" hint="Ein bis zwei Sätze für die Klasse (freiwillig)." />
    <x-ui.input label="Läuft bis" name="ends_on" id="{{ $prefix }}-ends" type="date" :value="old('ends_on', $list?->ends_on?->toDateString())" hint="Danach ist die Liste für die Klasse nicht mehr sichtbar (freiwillig)." />
</div>
<label class="bc-public-catalog-filter__check">
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $list?->is_published ?? true))>
    <span>Für die Klasse sichtbar</span>
</label>
