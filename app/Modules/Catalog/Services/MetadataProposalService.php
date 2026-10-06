<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\DTOs\BibliographicLookupResult;
use App\Modules\Catalog\DTOs\BibliographicRecord;
use App\Modules\Catalog\DTOs\MetadataProposal;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Models\Edition;
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
    ) {}

    public function propose(CatalogMetadataReview $review): CatalogMetadataReview
    {
        /** @var Edition $edition */
        $edition = Edition::query()->with('title.contributions.contributor')->findOrFail($review->edition_id);
        $fingerprint = $this->fingerprint->for($edition);

        [$record, $source, $available, $warnings] = $this->findRecord($edition);

        $proposal = $this->builder->build($edition, $record, $source, $fingerprint, $warnings);

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
            return [$result->records[0], MetadataProposal::SOURCE_DNB_ISBN, true, $warnings];
        }

        if (count($result->records) > 1) {
            $warnings[] = 'Zur ISBN gibt es mehrere Datensätze bei der DNB. Ohne eindeutigen Treffer wird nichts vorgeschlagen.';
        } elseif ($result->records === []) {
            $warnings[] = 'Die DNB kennt zu dieser ISBN keinen passenden Datensatz. Möglicherweise ist die ISBN falsch erfasst.';
        }

        return [null, MetadataProposal::SOURCE_LOCAL, true, $warnings];
    }
}
