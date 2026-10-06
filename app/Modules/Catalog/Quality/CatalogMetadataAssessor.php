<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Quality;

use App\Modules\Catalog\Enums\MetadataIssue;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;

/**
 * Bewertet die Metadaten einer Ausgabe. Rein lesend und ohne Datenbankzugriff: Die Ausgabe muss mit ihrem
 * Titel geladen sein, die Namen der Verantwortlichen werden übergeben.
 */
final class CatalogMetadataAssessor
{
    /**
     * Bewertet eine Ausgabe samt Verantwortlichen. `title.contributions.contributor` muss geladen sein.
     *
     * @return list<MetadataIssue>
     */
    public function assessEdition(Edition $edition): array
    {
        return $this->assess($edition, $this->contributorNames($edition));
    }

    /**
     * @param  list<string>  $contributorNames  Anzeige- und Sortiernamen der Verantwortlichen des Titels
     * @return list<MetadataIssue>
     */
    public function assess(Edition $edition, array $contributorNames): array
    {
        $issues = [];

        $allTexts = [];

        foreach (MetadataFields::all() as $key => $field) {
            if ($field['text']) {
                $allTexts[] = MetadataFields::value($edition, $key);
            }
        }

        $lostCharacterTexts = array_map(
            static fn (string $key): ?string => MetadataFields::value($edition, $key),
            MetadataFields::lostCharacterFields(),
        );

        array_push($allTexts, ...$contributorNames);
        array_push($lostCharacterTexts, ...$contributorNames);

        if ($this->any($allTexts, QualityText::hasControlCharacters(...))) {
            $issues[] = MetadataIssue::ControlCharacters;
        }

        if ($this->any($lostCharacterTexts, QualityText::hasLostCharacters(...))) {
            $issues[] = MetadataIssue::LostCharacters;
        }

        if ($this->any($allTexts, QualityText::hasMojibake(...))) {
            $issues[] = MetadataIssue::Mojibake;
        }

        if ($contributorNames === []) {
            $issues[] = MetadataIssue::MissingContributors;
        }

        if (MetadataFields::value($edition, 'edition.publication_year') === null) {
            $issues[] = MetadataIssue::MissingYear;
        }

        if (MetadataFields::value($edition, 'edition.publisher_name') === null) {
            $issues[] = MetadataIssue::MissingPublisher;
        }

        if (MetadataFields::value($edition, 'edition.media_type') === null) {
            $issues[] = MetadataIssue::MissingMediaType;
        }

        $isbn = MetadataFields::value($edition, 'edition.isbn');

        if ($isbn === null) {
            $issues[] = MetadataIssue::MissingIsbn;
        } elseif (! (new CatalogIsbnNormalizer)->hasValidChecksum($isbn)) {
            $issues[] = MetadataIssue::InvalidIsbn;
        }

        $summary = MetadataFields::value($edition, 'edition.summary');

        if ($summary === null || QualityText::isPlaceholderSummary($summary)) {
            $issues[] = MetadataIssue::MissingSummary;
        }

        if (MetadataFields::value($edition, 'edition.subject_keywords') === null) {
            $issues[] = MetadataIssue::MissingKeywords;
        }

        return $issues;
    }

    /** @return list<string> */
    public function contributorNames(Edition $edition): array
    {
        $names = [];

        foreach ($edition->title->contributions as $link) {
            foreach ([$link->contributor->display_name, $link->contributor->sort_name] as $name) {
                if (is_string($name) && trim($name) !== '') {
                    $names[] = $name;
                }
            }
        }

        return array_values(array_unique($names));
    }

    /** @param list<MetadataIssue> $issues */
    public function severity(array $issues): int
    {
        return array_sum(array_map(static fn (MetadataIssue $issue): int => $issue->weight(), $issues));
    }

    /**
     * @param  list<string|null>  $values
     * @param  callable(?string): bool  $check
     */
    private function any(array $values, callable $check): bool
    {
        foreach ($values as $value) {
            if ($check($value)) {
                return true;
            }
        }

        return false;
    }
}
