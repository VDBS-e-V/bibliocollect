{{-- Formular für eine Bereichsgruppe, einen Bereich oder ein Regal. Neu: $section ist null und $kind sowie $parentId sind gesetzt. --}}
@php
    $key = $section?->getKey() ?? $kind->value.'-new-'.($parentId ?? 'root');
@endphp
<form method="post" action="{{ $section ? route('administration.sections.update', ['sectionId' => $section->getKey()]) : route('administration.sections.store') }}" class="bc-loc-form">
    @csrf
    @if ($section)
        @method('PATCH')
    @else
        <input type="hidden" name="kind" value="{{ $kind->value }}">
        @if ($parentId)
            <input type="hidden" name="parent_id" value="{{ $parentId }}">
        @endif
    @endif
    <x-ui.input :label="$kind->label().' (Kennung)'" name="code" :id="'section-code-'.$key" :value="$section?->code" maxlength="20" required :hint="$hint ?? null" />
    <x-ui.input label="Name (optional)" name="name" :id="'section-name-'.$key" :value="$section?->name" maxlength="120" />
    <x-ui.input label="Beschreibung (optional)" name="description" :id="'section-desc-'.$key" :value="$section?->description" maxlength="300" />
    <x-ui.input label="Reihenfolge" name="sort_order" :id="'section-order-'.$key" type="number" min="0" max="9999" :value="$section?->sort_order ?? $nextOrder ?? 0" />
    <x-ui.button type="submit" :variant="$section ? 'secondary' : 'primary'">{{ $section ? 'Speichern' : $kind->label().' anlegen' }}</x-ui.button>
</form>
