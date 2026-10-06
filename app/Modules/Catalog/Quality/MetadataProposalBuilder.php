<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Quality;

use App\Modules\Catalog\DTOs\BibliographicRecord;
use App\Modules\Catalog\DTOs\MetadataChange;
use App\Modules\Catalog\DTOs\MetadataProposal;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\TitleContribution;
use App\Modules\Catalog\Services\ContributorResolver;
use Illuminate\Support\Collection;

/**
 * Vergleicht eine Ausgabe mit einem externen Datensatz und leitet daraus Änderungsvorschläge ab.
 * Rein lesend: Es wird nichts gespeichert.
 *
 * Grundregeln:
 *  - Ein gefülltes, unbeschädigtes Feld wird nie überschrieben. Weicht es nur ab, wird die Abweichung gezeigt,
 *    aber nicht vorausgewählt.
 *  - Ein beschädigter Wert wird nur korrigiert, wenn der Quellwert nachweislich sein Ursprung ist.
 *  - Passt der Datensatz nicht zur Ausgabe, wird nichts vorausgewählt.
 */
final readonly class MetadataProposalBuilder
{
    private const MIN_SIMILARITY_BY_ID = 40.0;

    private const MIN_SIMILARITY_BY_ISBN = 55.0;

    public function __construct(private ContributorResolver $contributors) {}

    /**
     * Ausgabe mit `title.contributions.contributor` muss geladen sein.
     *
     * @param  list<string>  $warnings
     */
    public function build(Edition $edition, ?BibliographicRecord $record, string $source, string $fingerprint, array $warnings = []): MetadataProposal
    {
        $plausible = true;

        if ($record !== null) {
            foreach ($this->plausibilityWarnings($edition, $record, $source) as $warning) {
                $warnings[] = $warning;
                $plausible = false;
            }
        }

        $changes = [...$this->fieldChanges($edition, $record), ...($record !== null ? $this->contributorChanges($edition, $record) : [])];

        if (! $plausible) {
            $changes = array_map(static fn (MetadataChange $change): MetadataChange => $change->withSelected(false), $changes);
        }

        return new MetadataProposal(
            source: $record !== null ? $source : MetadataProposal::SOURCE_LOCAL,
            recordId: $record?->sourceRecordId,
            permalink: $record?->sourcePermalink,
            fingerprint: $fingerprint,
            warnings: $warnings,
            changes: $changes,
        );
    }

    /** @return list<MetadataChange> */
    private function fieldChanges(Edition $edition, ?BibliographicRecord $record): array
    {
        $changes = [];

        foreach (MetadataFields::all() as $key => $field) {
            $change = $this->fieldChange(
                $key,
                $field['label'],
                $field['text'],
                MetadataFields::value($edition, $key),
                $record !== null ? $this->recordValue($record, $key) : null,
            );

            if ($change !== null) {
                $changes[] = $change;
            }
        }

        return $changes;
    }

    private function fieldChange(string $key, string $label, bool $text, ?string $current, ?string $proposed): ?MetadataChange
    {
        $cleaned = $text && $current !== null ? QualityText::clean($current) : $current;
        $needsCleaning = $text && $current !== null && $cleaned !== $current;

        if ($proposed === null) {
            return $needsCleaning ? new MetadataChange($key, MetadataChange::LOCAL, $label, $current, $cleaned, true) : null;
        }

        if ($current === null) {
            return new MetadataChange($key, MetadataChange::FILL, $label, null, $proposed, true);
        }

        if ($this->equal($key, $text, $current, $proposed)) {
            return $needsCleaning ? new MetadataChange($key, MetadataChange::LOCAL, $label, $current, $cleaned, true) : null;
        }

        // Eine vorhandene ISBN wird nie ersetzt: Ein Unterschied ist meist eine andere Ausgabe, kein Fehler.
        if ($key === 'edition.isbn') {
            return null;
        }

        if ($text && QualityText::sameAfterLoss($current, $proposed)) {
            return new MetadataChange($key, MetadataChange::FIX, $label, $current, $proposed, true);
        }

        if ($needsCleaning) {
            return new MetadataChange($key, MetadataChange::LOCAL, $label, $current, $cleaned, true);
        }

        return new MetadataChange($key, MetadataChange::DIFFERS, $label, $current, $proposed, false);
    }

    private function equal(string $key, bool $text, string $current, string $proposed): bool
    {
        if ($key === 'edition.isbn') {
            return $this->digits($current) === $this->digits($proposed);
        }

        return $text || $key !== 'edition.publication_year'
            ? QualityText::same($current, $proposed)
            : trim($current) === trim($proposed);
    }

    private function digits(string $isbn): string
    {
        return strtoupper(preg_replace('/[^0-9Xx]/', '', $isbn) ?? $isbn);
    }

    private function recordValue(BibliographicRecord $record, string $key): ?string
    {
        return match ($key) {
            'title.preferred_title' => $record->title,
            'title.subtitle' => $record->subtitle,
            'edition.responsibility_statement' => $record->responsibilityStatement,
            'edition.publisher_name' => $record->publisherName,
            'edition.publication_place' => $record->publicationPlace,
            'edition.publication_year' => $record->publicationYear !== null ? (string) $record->publicationYear : null,
            'edition.edition_statement' => $record->editionStatement,
            'edition.physical_extent' => $record->physicalExtent,
            'edition.language_code' => $record->languageCode,
            'edition.original_language_code' => $record->originalLanguageCode,
            'edition.media_type' => $record->mediaType,
            'edition.series_statement' => $record->seriesStatement,
            'edition.summary' => $record->summary,
            'edition.subject_keywords' => $record->subjectKeywords,
            'edition.target_audience' => $record->targetAudience,
            'edition.isbn' => $record->isbn,
            default => null,
        };
    }

    /** @return list<MetadataChange> */
    private function contributorChanges(Edition $edition, BibliographicRecord $record): array
    {
        $links = $edition->title->contributions;
        $changes = [];
        $handled = [];
        $addIndex = 0;

        foreach ($record->contributors as $external) {
            $match = $this->matchContributor($links, $external);

            if ($match === null) {
                $changes[] = new MetadataChange(
                    key: 'contributor.add.'.$addIndex++,
                    kind: MetadataChange::ADD,
                    label: 'Verantwortliche:r',
                    current: null,
                    proposed: $external['name'].' ('.MetadataFields::roleLabel($external['role']).')',
                    // Nur vorauswählen, wenn die Ausgabe bisher gar keine Verantwortlichen hat.
                    selected: $links->isEmpty(),
                    payload: ['name' => $external['name'], 'role' => $external['role'], 'gnd_id' => $external['gnd_id']],
                );

                continue;
            }

            $contributor = $match->contributor;

            if (isset($handled[$contributor->getKey()]) || ! $this->isDamaged($contributor)) {
                continue;
            }

            $handled[$contributor->getKey()] = true;
            $changes[] = new MetadataChange(
                key: 'contributor.rename.'.$contributor->getKey(),
                kind: MetadataChange::RENAME,
                label: 'Name von '.$this->contributors->displayName($external['name']),
                current: $this->nameLine($contributor->display_name, $contributor->sort_name),
                proposed: $this->nameLine($this->contributors->displayName($external['name']), $this->contributors->sortName($external['name'])),
                selected: true,
                payload: [
                    'contributor_id' => (string) $contributor->getKey(),
                    'display_name' => $this->contributors->displayName($external['name']),
                    'sort_name' => $this->contributors->sortName($external['name']),
                    'gnd_id' => $this->freeGnd($contributor, $external['gnd_id']),
                    'shared_titles' => TitleContribution::query()
                        ->where('contributor_id', $contributor->getKey())
                        ->where('title_id', '!=', $edition->title_id)
                        ->distinct()
                        ->count('title_id'),
                ],
            );
        }

        return $changes;
    }

    /**
     * @param  Collection<int, TitleContribution>  $links
     * @param  array{name: string, role: string, gnd_id: string|null}  $external
     */
    private function matchContributor(Collection $links, array $external): ?TitleContribution
    {
        $display = $this->contributors->displayName($external['name']);

        foreach ($links as $link) {
            $contributor = $link->contributor;

            if ($external['gnd_id'] !== null && $contributor->gnd_id === $external['gnd_id']) {
                return $link;
            }

            if (
                QualityText::same($contributor->sort_name, $external['name'])
                || QualityText::same($contributor->display_name, $display)
                || QualityText::same($contributor->display_name, $external['name'])
                || QualityText::sameAfterLoss($contributor->sort_name, $external['name'])
                || QualityText::sameAfterLoss($contributor->display_name, $display)
                || QualityText::sameAfterLoss($contributor->display_name, $external['name'])
            ) {
                return $link;
            }
        }

        return null;
    }

    private function isDamaged(Contributor $contributor): bool
    {
        foreach ([$contributor->display_name, $contributor->sort_name] as $name) {
            if (QualityText::hasLostCharacters($name) || QualityText::hasControlCharacters($name)) {
                return true;
            }
        }

        return false;
    }

    /** Eine GND-ID wird nur mitgegeben, wenn die Person noch keine hat und die ID nicht schon vergeben ist. */
    private function freeGnd(Contributor $contributor, ?string $gndId): ?string
    {
        if ($gndId === null || $contributor->gnd_id !== null) {
            return null;
        }

        return Contributor::query()->where('gnd_id', $gndId)->exists() ? null : $gndId;
    }

    private function nameLine(string $display, ?string $sort): string
    {
        return $sort !== null && $sort !== '' ? $display.' · '.$sort : $display;
    }

    /** @return list<string> */
    private function plausibilityWarnings(Edition $edition, BibliographicRecord $record, string $source): array
    {
        $warnings = [];

        if (! $this->titleMatches($edition, $record, $source)) {
            $warnings[] = 'Der Titel der Quelle („'.$record->title.'“) weicht stark vom Titel im Katalog ab. '
                .'Bitte prüfen, ob es wirklich dasselbe Medium ist. Es wurde nichts vorausgewählt.';
        }

        $isbn = MetadataFields::value($edition, 'edition.isbn');

        if ($isbn !== null && $record->isbn !== null && $this->digits($isbn) !== $this->digits($record->isbn)) {
            $warnings[] = 'Die ISBN der Quelle ('.$record->isbn.') weicht von der ISBN im Katalog ab. '
                .'Möglicherweise ist es eine andere Ausgabe. Es wurde nichts vorausgewählt.';
        }

        return $warnings;
    }

    private function titleMatches(Edition $edition, BibliographicRecord $record, string $source): bool
    {
        $current = QualityText::clean($edition->title->preferred_title);
        $candidates = [$record->title, trim($record->title.' '.($record->subtitle ?? ''))];
        $threshold = $source === MetadataProposal::SOURCE_DNB_ID ? self::MIN_SIMILARITY_BY_ID : self::MIN_SIMILARITY_BY_ISBN;
        $plainCurrent = $this->plain($current);

        foreach ($candidates as $candidate) {
            if (QualityText::same($current, $candidate) || QualityText::sameAfterLoss($current, $candidate)) {
                return true;
            }

            $plainCandidate = $this->plain($candidate);

            if (strlen($plainCurrent) >= 4 && strlen($plainCandidate) >= 4
                && (str_contains($plainCandidate, $plainCurrent) || str_contains($plainCurrent, $plainCandidate))) {
                return true;
            }

            similar_text($plainCurrent, $plainCandidate, $percent);

            if ($percent >= $threshold) {
                return true;
            }
        }

        return false;
    }

    private function plain(string $value): string
    {
        return mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? $value);
    }
}
