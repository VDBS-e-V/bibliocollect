<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

/**
 * Vorschlag zu einer Ausgabe: die Quelle, Warnhinweise und die einzelnen Änderungen.
 * Gilt nur für den Datenstand, dessen Prüfsumme in {@see self::$fingerprint} steht.
 */
final readonly class MetadataProposal
{
    public const SOURCE_DNB_ID = 'dnb-id';

    public const SOURCE_DNB_ISBN = 'dnb-isbn';

    public const SOURCE_OPENLIBRARY_ISBN = 'openlibrary-isbn';

    public const SOURCE_GOOGLEBOOKS_ISBN = 'googlebooks-isbn';

    public const SOURCE_LOCAL = 'local';

    /**
     * @param  list<string>  $warnings
     * @param  list<MetadataChange>  $changes
     */
    public function __construct(
        public string $source,
        public ?string $recordId,
        public ?string $permalink,
        public string $fingerprint,
        public array $warnings,
        public array $changes,
    ) {}

    public function change(string $key): ?MetadataChange
    {
        foreach ($this->changes as $change) {
            if ($change->key === $key) {
                return $change;
            }
        }

        return null;
    }

    /**
     * Vorschläge, die man übernehmen kann (ohne reine Abweichungen).
     *
     * @return list<MetadataChange>
     */
    public function suggestions(): array
    {
        return array_values(array_filter($this->changes, static fn (MetadataChange $change): bool => ! $change->isDifference()));
    }

    /** @return list<MetadataChange> */
    public function differences(): array
    {
        return array_values(array_filter($this->changes, static fn (MetadataChange $change): bool => $change->isDifference()));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'record_id' => $this->recordId,
            'permalink' => $this->permalink,
            'fingerprint' => $this->fingerprint,
            'warnings' => $this->warnings,
            'changes' => array_map(static fn (MetadataChange $change): array => $change->toArray(), $this->changes),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $changes = [];

        if (is_array($data['changes'] ?? null)) {
            foreach ($data['changes'] as $change) {
                if (is_array($change)) {
                    /** @var array<string, mixed> $change */
                    $changes[] = MetadataChange::fromArray($change);
                }
            }
        }

        $warnings = [];

        if (is_array($data['warnings'] ?? null)) {
            foreach ($data['warnings'] as $warning) {
                if (is_string($warning)) {
                    $warnings[] = $warning;
                }
            }
        }

        return new self(
            source: is_string($data['source'] ?? null) ? $data['source'] : self::SOURCE_LOCAL,
            recordId: is_string($data['record_id'] ?? null) ? $data['record_id'] : null,
            permalink: is_string($data['permalink'] ?? null) ? $data['permalink'] : null,
            fingerprint: is_string($data['fingerprint'] ?? null) ? $data['fingerprint'] : '',
            warnings: $warnings,
            changes: $changes,
        );
    }
}
