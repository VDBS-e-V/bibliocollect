<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\DTOs\BibliographicLookupResult;
use App\Modules\Catalog\DTOs\BibliographicRecord;
use App\Modules\Catalog\DTOs\MetadataChange;
use App\Modules\Catalog\DTOs\MetadataProposal;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Quality\MetadataFields;
use App\Modules\Catalog\Quality\MetadataFingerprint;
use App\Modules\Catalog\Quality\MetadataProposalBuilder;

/**
 * Holt zu einem Prüffall den Datensatz der Quelle (erst über die DNB-ID, sonst über die ISBN), baut den
 * Vorschlag und speichert ihn am Fall. Der Katalog selbst wird dabei nicht verändert.
 */
final readonly class MetadataProposalService
{
    public const STATE_READY = 'ready';

    public const STATE_NONE = 'none';

    public const STATE_UNAVAILABLE = 'unavailable';

    public function __construct(
        private BibliographicLookupService $lookup,
        private MetadataProposalBuilder $builder,
        private MetadataFingerprint $fingerprint,
        private CatalogSummaryService $summaries,
    ) {}

    public function propose(CatalogMetadataReview $review): CatalogMetadataReview
    {
        /** @var Edition $edition */
        $edition = Edition::query()->with('title.contributions.contributor')->findOrFail($review->edition_id);
        $fingerprint = $this->fingerprint->for($edition);

        [$record, $source, $available, $warnings] = $this->findRecord($edition);

        $proposal = $this->withSummary($edition, $this->builder->build($edition, $record, $source, $fingerprint, $warnings));

        $state = match (true) {
            $proposal->changes !== [] || $record !== null => self::STATE_READY,
            ! $available => self::STATE_UNAVAILABLE,
            default => self::STATE_NONE,
        };

        $review->forceFill([
            'fingerprint' => $fingerprint,
            'proposal' => $proposal->toArray(),
            'proposal_source' => $proposal->source,
            'proposal_state' => $state,
            'proposal_fetched_at' => now(),
        ])->save();

        return $review;
    }

    /**
     * Fehlt die Zusammenfassung und liefert die DNB keine, wird eine aus Google Books oder Open Library vorgeschlagen (Anreicherung).
     * Der Hinweis nennt die Quelle und schließt den Fall von der gesammelten Übernahme aus: Ein Klappentext soll jemand lesen.
     */
    private function withSummary(Edition $edition, MetadataProposal $proposal): MetadataProposal
    {
        if (! config('catalog.quality.summary_enrichment', true)) {
            return $proposal;
        }

        $has = static fn (string $key): bool => count(array_filter($proposal->changes, static fn (MetadataChange $change): bool => $change->key === $key)) > 0;

        if ($has('edition.summary') || MetadataFields::value($edition, 'edition.summary') !== null || ! is_string($edition->isbn) || trim($edition->isbn) === '') {
            return $proposal;
        }

        $found = $this->summaries->findByIsbn($edition->isbn);

        if ($found === null) {
            return $proposal;
        }

        return new MetadataProposal(
            source: $proposal->source,
            recordId: $proposal->recordId,
            permalink: $proposal->permalink,
            fingerprint: $proposal->fingerprint,
            warnings: [...$proposal->warnings, 'Die Zusammenfassung stammt von '.$found['source'].'. Bitte kurz lesen; sie wird nicht gesammelt übernommen.'],
            changes: [...$proposal->changes, new MetadataChange('edition.summary', MetadataChange::FILL, 'Zusammenfassung', null, $found['text'], true, ['source' => $found['source']])],
        );
    }

    /** Kennung der Quelle am Vorschlag; bei gemischten Quellen („openlibrary+googlebooks“) zählt die erste. */
    private function sourceFor(string $recordSource): string
    {
        return match (explode('+', $recordSource)[0]) {
            'googlebooks' => MetadataProposal::SOURCE_GOOGLEBOOKS_ISBN,
            default => MetadataProposal::SOURCE_OPENLIBRARY_ISBN,
        };
    }

    /** @return array{0: BibliographicRecord|null, 1: string, 2: bool, 3: list<string>} Treffer, Quelle, Quelle erreichbar, Hinweise */
    private function findRecord(Edition $edition): array
    {
        $available = true;
        $warnings = [];

        $recordId = is_string($edition->source_record_id) && trim($edition->source_record_id) !== ''
            ? trim($edition->source_record_id)
            : null;

        if ($recordId !== null) {
            $result = $this->lookup->byRecordId($recordId);
            $available = $result->available;

            if ($result->records !== []) {
                return [$result->records[0], MetadataProposal::SOURCE_DNB_ID, true, []];
            }

            if (! $available) {
                return [null, MetadataProposal::SOURCE_LOCAL, false, ['Die DNB ist derzeit nicht erreichbar. Es werden nur lokale Bereinigungen vorgeschlagen.']];
            }

            $warnings[] = 'Zur gespeicherten DNB-ID '.$recordId.' wurde kein Datensatz gefunden.';
        }

        $isbn = is_string($edition->isbn) && trim($edition->isbn) !== '' ? trim($edition->isbn) : null;

        if ($isbn !== null) {
            $result = $this->lookup->byIsbn($isbn);

            return $this->fromIsbn($result, $warnings);
        }

        return [null, MetadataProposal::SOURCE_LOCAL, $available, $warnings];
    }

    /**
     * @param  list<string>  $warnings
     * @return array{0: BibliographicRecord|null, 1: string, 2: bool, 3: list<string>}
     */
    private function fromIsbn(BibliographicLookupResult $result, array $warnings): array
    {
        if (! $result->available) {
            $warnings[] = 'Die DNB ist derzeit nicht erreichbar. Es werden nur lokale Bereinigungen vorgeschlagen.';

            return [null, MetadataProposal::SOURCE_LOCAL, false, $warnings];
        }

        if (count($result->records) === 1) {
            $record = $result->records[0];

            if ($record->isAuthoritative()) {
                return [$record, MetadataProposal::SOURCE_DNB_ISBN, true, $warnings];
            }

            // Treffer aus Open Library oder Google Books: brauchbar, aber weniger verlässlich. Die Warnung schließt den Fall von der gesammelten Übernahme aus.
            $warnings[] = 'Die Angaben stammen aus '.$record->sourceLabel().' und sind weniger verlässlich als die der DNB. Bitte jede Änderung einzeln prüfen; der Fall wird nicht gesammelt übernommen.';

            return [$record, $this->sourceFor($record->source), true, $warnings];
        }

        if (count($result->records) > 1) {
            $warnings[] = 'Zur ISBN gibt es mehrere Datensätze bei der DNB. Ohne eindeutigen Treffer wird nichts vorgeschlagen.';
        } elseif ($result->records === []) {
            $warnings[] = 'Die DNB kennt zu dieser ISBN keinen passenden Datensatz. Möglicherweise ist die ISBN falsch erfasst.';
        }

        return [null, MetadataProposal::SOURCE_LOCAL, true, $warnings];
    }
}
