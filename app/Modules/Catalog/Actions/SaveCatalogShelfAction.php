<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Enums\ShelfSectionKind;
use App\Modules\Catalog\Exceptions\CatalogShelfStructureConflict;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogShelfSection;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Services\CatalogShelfStructure;
use Illuminate\Support\Facades\DB;

/**
 * Legt ein Regalbrett an oder ändert es. Liegt es in einem Regal, ist der Standort aus Bereichsgruppe, Bereich, Regal und der
 * Bezeichnung des Bretts zusammengesetzt („I. A 1 a“); sonst ist der Code frei. Wird der Code geändert, ziehen die Exemplare mit.
 * Die Themenbereiche steuern den Vorschlag beim Einsortieren.
 */
final readonly class SaveCatalogShelfAction
{
    public function __construct(private AuditRecorder $audit, private CatalogShelfStructure $structure) {}

    /**
     * @param  list<string>  $topicIds
     *
     * @throws CatalogShelfStructureConflict
     */
    public function execute(?CatalogShelf $shelf, ?string $code, ?string $label, int $sortOrder, bool $active, array $topicIds = [], ?string $rackId = null, ?string $board = null): CatalogShelf
    {
        return DB::transaction(function () use ($shelf, $code, $label, $sortOrder, $active, $topicIds, $rackId, $board): CatalogShelf {
            $label = $label !== null && trim($label) !== '' ? trim($label) : null;
            $rack = $rackId !== null && $rackId !== '' ? CatalogShelfSection::query()->with('parent.parent')->find($rackId) : null;

            if ($rack !== null && $rack->kind !== ShelfSectionKind::Rack) {
                throw CatalogShelfStructureConflict::notInLevel(ShelfSectionKind::Rack->label());
            }

            $board = $board !== null && trim($board) !== '' ? trim($board) : null;
            $code = $rack !== null && $board !== null ? $this->structure->codeFor($rack, $board) : trim((string) $code);

            if (CatalogShelf::query()->where('code', $code)->when($shelf !== null, static fn ($query) => $query->whereKeyNot($shelf?->getKey()))->exists()) {
                throw CatalogShelfStructureConflict::duplicate($code);
            }

            $values = ['code' => $code, 'label' => $label, 'section_id' => $rack?->getKey(), 'board' => $rack !== null ? $board : null, 'sort_order' => $sortOrder, 'is_active' => $active];

            if ($shelf === null) {
                $shelf = CatalogShelf::query()->create($values);
                $this->syncTopics($shelf, $topicIds);
                $this->audit->record('catalog.shelf.created', 'Regalbrett angelegt.', $shelf);

                return $shelf;
            }

            $oldCode = $shelf->code;

            $shelf->forceFill($values)->save();
            $this->syncTopics($shelf, $topicIds);

            if ($oldCode !== $code) {
                Copy::query()->where('shelf_location', $oldCode)->update(['shelf_location' => $code]);
            }

            $this->audit->record('catalog.shelf.updated', 'Regalbrett geändert.', $shelf, ['renamed' => $oldCode !== $code]);

            return $shelf;
        });
    }

    /** @param  list<string>  $topicIds */
    private function syncTopics(CatalogShelf $shelf, array $topicIds): void
    {
        $payload = [];

        foreach (array_values(array_unique($topicIds)) as $index => $topicId) {
            $payload[$topicId] = ['position' => $index + 1];
        }

        $shelf->topics()->sync($payload);
    }
}
