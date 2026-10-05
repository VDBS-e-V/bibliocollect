<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Support;

use App\Modules\Catalog\Enums\CatalogImportField;
use App\Modules\Catalog\Enums\CatalogImportRowStatus;
use App\Modules\Catalog\Enums\CatalogImportStatus;
use App\Modules\Catalog\Models\CatalogImportRow;

final class CatalogImportPresenter
{
    public function fieldLabel(CatalogImportField $field): string
    {
        return match ($field) {
            CatalogImportField::PreferredTitle => 'Haupttitel',
            CatalogImportField::Subtitle => 'Untertitel',
            CatalogImportField::SortTitle => 'Sortiertitel',
            CatalogImportField::EditionStatement => 'Edition / Auflage',
            CatalogImportField::Isbn => 'ISBN',
            CatalogImportField::PublisherName => 'Verlag',
            CatalogImportField::PublicationYear => 'Erscheinungsjahr',
            CatalogImportField::MediaType => 'Medientyp',
            CatalogImportField::LanguageCode => 'Sprache',
            CatalogImportField::MinimumAge => 'Mindestalter',
            CatalogImportField::AgeRatingLabel => 'Altersfreigabe-Label',
            CatalogImportField::ContributorName => 'Contributor Name',
            CatalogImportField::ContributorSortName => 'Contributor Sort Name',
            CatalogImportField::ContributorRole => 'Contributor Role',
            CatalogImportField::Barcode => 'Barcode',
            CatalogImportField::ShelfLocation => 'Regalstandort',
            CatalogImportField::CopyStatus => 'Copy Status',
        };
    }

    public function batchStatus(CatalogImportStatus $status): string
    {
        return match ($status) {
            CatalogImportStatus::Uploaded => 'Hochgeladen',
            CatalogImportStatus::Previewed => 'Vorschau wird ausgewertet',
            CatalogImportStatus::Ready => 'Bereit zur Übernahme',
            CatalogImportStatus::Blocked => 'Blockiert',
            CatalogImportStatus::Committed => 'Übernommen',
            CatalogImportStatus::Failed => 'Fehlgeschlagen',
        };
    }

    public function rowStatus(CatalogImportRowStatus $status): string
    {
        return match ($status) {
            CatalogImportRowStatus::Pending => 'Noch nicht geprüft',
            CatalogImportRowStatus::Valid => 'Gültig',
            CatalogImportRowStatus::Invalid => 'Ungültig',
            CatalogImportRowStatus::Conflict => 'Konflikt',
        };
    }

    public function planSummary(CatalogImportRow $row): string
    {
        if (! is_array($row->plan)) {
            return '—';
        }

        $parts = [];

        foreach ([
            'title' => ['Titel', 'neu', 'wiederverwenden'],
            'edition' => ['Ausgabe', 'neu', 'wiederverwenden'],
            'contributor' => ['Contributor', 'neu', 'wiederverwenden'],
            'contribution' => ['Verantwortlichkeit', 'neu', 'vorhanden'],
            'copy' => ['Exemplar', 'neu', 'vorhanden'],
        ] as $key => [$label, $createLabel, $reuseLabel]) {
            $item = $row->plan[$key] ?? null;

            if (! is_array($item)) {
                continue;
            }

            $parts[] = $label.': '.(($item['mode'] ?? null) === 'create' ? $createLabel : $reuseLabel);
        }

        return implode(' · ', $parts);
    }
}
