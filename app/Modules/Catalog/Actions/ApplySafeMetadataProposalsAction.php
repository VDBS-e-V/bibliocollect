<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Exceptions\MetadataProposalOutdated;
use App\Modules\Catalog\Queries\SafeMetadataProposalsQuery;
use InvalidArgumentException;

/**
 * Übernimmt alle eindeutigen Vorschläge nacheinander, je Fall in einer eigenen Transaktion über die gewohnte Einzelaktion.
 * Ein Fall, der sich zwischenzeitlich geändert hat, wird übersprungen und bleibt offen.
 */
final readonly class ApplySafeMetadataProposalsAction
{
    public function __construct(
        private SafeMetadataProposalsQuery $safe,
        private ApplyMetadataProposalAction $apply,
    ) {}

    /** @return array{applied: int, changes: int, skipped: int} */
    public function execute(int $userId): array
    {
        $applied = 0;
        $changes = 0;
        $skipped = 0;

        foreach ($this->safe->execute() as $entry) {
            try {
                $this->apply->execute($entry['review'], $entry['keys'], $userId);
                $applied++;
                $changes += count($entry['keys']);
            } catch (MetadataProposalOutdated|InvalidArgumentException) {
                $skipped++;
            }
        }

        return ['applied' => $applied, 'changes' => $changes, 'skipped' => $skipped];
    }
}
