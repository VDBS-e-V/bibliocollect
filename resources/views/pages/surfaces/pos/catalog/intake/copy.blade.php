@php
    use App\Surfaces\Pos\Support\CatalogIntakeVocabulary;

    $existing = $context['existing'];
    $currentStatus = old('status', $copy['status']->value ?? 'active');
    $currentShelf = old('shelf_location', $copy['shelf_location'] ?? '');
    $backRoute = $detailsSkipped ? route('pos.catalog.intake.matches') : route('pos.catalog.intake.details');
@endphp

<x-app-shell surface="pos" title="Exemplar">
    <x-ui.page-header
        kicker="Medium erfassen"
        title="Exemplar"
        :lead="$context['title']"
    />

    <x-catalog.intake-steps :current="5" :skip-details="$detailsSkipped" />

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">Bitte prüfe die markierten Eingaben.</x-ui.alert>
    @endif

    @if ($existing)
        <x-ui.alert variant="info" title="Weiteres Exemplar">
            Es wird ein zusätzliches Exemplar zur vorhandenen Ausgabe
            ({{ $existing->publisher_name ?: 'Verlag unbekannt' }}@if ($existing->publication_year), {{ $existing->publication_year }}@endif@if ($existing->isbn), ISBN {{ $existing->isbn }}@endif)
            angelegt. Titel- und Ausgabedaten bleiben unverändert.
        </x-ui.alert>
    @endif

    <form method="post" action="{{ route('pos.catalog.intake.copy.store') }}" class="bc-intake-form">
        @csrf

        <fieldset class="bc-intake-fieldset">
            <legend>Exemplar</legend>

            <dl class="bc-intake-summary">
                <dt>Inventarnummer</dt>
                <dd>
                    <strong>{{ $barcode }}</strong>
                    · <a href="{{ route('pos.catalog.intake.identify') }}">ändern</a>
                </dd>
            </dl>

            <div class="bc-intake-fieldset__grid">
                <x-ui.select label="Standort (Regalbrett)" name="shelf_location" :error="$errors->first('shelf_location')">
                    <option value="">Kein Standort</option>
                    @foreach ($shelfOptions as $code => $display)
                        <option value="{{ $code }}" @selected($currentShelf === $code)>{{ $display }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select label="Zustand im Bestand" name="status" :error="$errors->first('status')">
                    @foreach (CatalogIntakeVocabulary::copyStatuses() as $key => $label)
                        <option value="{{ $key }}" @selected($currentStatus === $key)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
            </div>
        </fieldset>

        <div class="bc-intake-actions">
            <a href="{{ $backRoute }}">← Zurück</a>
            <x-ui.button type="submit">Weiter zur Prüfung</x-ui.button>
        </div>
    </form>
</x-app-shell>
