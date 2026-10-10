<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Import\Classification;

/**
 * Ergebnis der Vorprüfung eines Themen- und Regalbrett-Imports. Enthält alles, was die Vorschau zeigt und der Import später schreibt;
 * geschrieben wird erst durch {@see ClassificationImporter}.
 */
final readonly class ClassificationImportPlan
{
    /**
     * @param  list<array{legacy_id: string, public_key: ?string, name: string, description: ?string, parent: ?string, status: string, changes: list<string>}>  $topics
     * @param  list<array{legacy_id: string, signature: string, topics: list<string>, status: string, shelf: string, shelf_status: string, new_links: int, new_signature_links: int}>  $signatures
     * @param  array<string, int>  $counts
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @param  list<string>  $conflicts
     */
    public function __construct(
        public array $topics,
        public array $signatures,
        public array $counts,
        public array $errors,
        public array $warnings,
        public array $conflicts,
        public string $fingerprint,
    ) {}

    /** Ob der Import ohne blockierende Fehler möglich ist und etwas zu tun hat. */
    public function canImport(): bool
    {
        return $this->errors === [] && ($this->topics !== [] || $this->signatures !== []);
    }

    /** Ob es überhaupt etwas zu schreiben gibt (sonst ist der Bestand schon auf dem Stand der Dateien). */
    public function hasChanges(): bool
    {
        foreach (['new_topics', 'new_signatures', 'new_shelves', 'new_assignments', 'new_signature_assignments'] as $key) {
            if (($this->counts[$key] ?? 0) > 0) {
                return true;
            }
        }

        return ($this->counts['changed_topics'] ?? 0) > 0;
    }
}
