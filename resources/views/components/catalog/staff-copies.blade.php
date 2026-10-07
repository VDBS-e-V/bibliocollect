@props(['title', 'states' => []])

@php
    use App\Modules\Catalog\Enums\CopyAccess;
    use App\Surfaces\Pos\Support\CatalogIntakeVocabulary;

    $statusLabels = CatalogIntakeVocabulary::copyStatuses();
    $rows = [];

    foreach ($title->editions as $edition) {
        $editionLabel = trim(($edition->publisher_name ?: 'Verlag unbekannt').($edition->publication_year ? ', '.$edition->publication_year : '').($edition->edition_statement ? ', '.$edition->edition_statement : ''));

        foreach ($edition->copies->sortBy('barcode') as $copy) {
            $state = $states[(string) $copy->getKey()] ?? null;

            if ($copy->status->value !== 'active') {
                $loanText = $statusLabels[$copy->status->value] ?? $copy->status->value;
                $loanKind = 'neutral';
            } elseif ($state?->loaned) {
                $loanText = 'ausgeliehen'.($state->dueOn ? ', fällig '.$state->dueOn->format('d.m.Y') : '');
                $loanKind = 'warning';
            } elseif ($state?->held) {
                $loanText = 'für Vormerkung zurückgelegt';
                $loanKind = 'warning';
            } else {
                $loanText = 'da';
                $loanKind = 'success';
            }

            $rows[] = [
                'copy' => $copy,
                'edition' => $edition,
                'editionLabel' => $editionLabel,
                'loanText' => $loanText,
                'loanKind' => $loanKind,
                'access' => CopyAccess::noteFor($copy->access_status),
            ];
        }
    }
@endphp

@if ($rows === [])
    <p class="bc-section-copy">Noch kein Exemplar erfasst.</p>
@else
    <x-ui.table>
        <thead>
            <tr>
                <th scope="col">Inventarnr.</th>
                <th scope="col">Ausgabe</th>
                <th scope="col">Stand</th>
                <th scope="col">Standort</th>
                <th scope="col">Zugänglichkeit</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <th scope="row" class="bc-tabular"><a href="{{ route('pos.catalog.copies.edit', ['editionId' => $row['edition']->getKey(), 'copyId' => $row['copy']->getKey()]) }}">{{ $row['copy']->barcode }}</a></th>
                    <td>{{ $row['editionLabel'] }}</td>
                    <td><x-ui.badge :variant="$row['loanKind']">{{ $row['loanText'] }}</x-ui.badge></td>
                    <td>{{ $row['copy']->shelf_location ?: 'noch nicht einsortiert' }}</td>
                    <td>{{ $row['access'] ?? 'Frei zugänglich' }}</td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table>
@endif
