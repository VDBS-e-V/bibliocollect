<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Import\Classification;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Legacy\LegacyCatalogNormalizer;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogShelfSection;
use App\Modules\Catalog\Models\CatalogSignature;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Services\CatalogShelfStructure;
use Illuminate\Support\Facades\DB;

/**
 * Schreibt einen geprüften {@see ClassificationImportPlan} in einer Transaktion. Es werden nur Themen, Signaturen, Regalbretter,
 * Standortstruktur und Themenzuordnungen angelegt oder ergänzt. Exemplare, Ausleihen, Vormerkungen, Inventarnummern und
 * Standorte vorhandener Exemplare bleiben unberührt; vorhandene Regalbretter behalten Reihenfolge, Schalter und Beschriftung.
 */
final readonly class ClassificationImporter
{
    public function __construct(private AuditRecorder $audit, private CatalogShelfStructure $structure) {}

    /**
     * @param  array<string, string|null>  $checksums  Prüfsummen der hochgeladenen Dateien (für das Protokoll)
     * @return array<string, int>
     */
    public function apply(ClassificationImportPlan $plan, bool $updateExisting, array $checksums = []): array
    {
        $result = DB::transaction(fn (): array => $this->write($plan, $updateExisting));

        $this->audit->record(
            'catalog.classification.imported',
            'Themen und Regalbretter aus Importdateien übernommen.',
            null,
            $result + ['topics_sha256' => $checksums['topics'] ?? null, 'signatures_sha256' => $checksums['signatures'] ?? null, 'update_existing' => $updateExisting],
        );

        return $result;
    }

    /** @return array<string, int> */
    private function write(ClassificationImportPlan $plan, bool $updateExisting): array
    {
        $result = [
            'new_topics' => 0,
            'updated_topics' => 0,
            'skipped_conflicts' => 0,
            'new_signatures' => 0,
            'existing_signatures' => 0,
            'new_shelves' => 0,
            'existing_shelves' => 0,
            'new_assignments' => 0,
            'new_signature_assignments' => 0,
        ];

        $byLegacy = CatalogTopic::query()->where('legacy_source', LegacyCatalogNormalizer::SOURCE)->get()->keyBy('legacy_id')->all();
        $parentsToSet = [];

        foreach ($plan->topics as $row) {
            $topic = $byLegacy[$row['legacy_id']] ?? null;

            if ($topic === null) {
                $topic = CatalogTopic::query()->create([
                    'legacy_source' => LegacyCatalogNormalizer::SOURCE,
                    'legacy_id' => $row['legacy_id'],
                    'public_key' => $row['public_key'],
                    'parent_id' => null,
                    'name' => $row['name'],
                    'description' => $row['description'],
                ]);
                $byLegacy[$row['legacy_id']] = $topic;
                $parentsToSet[$row['legacy_id']] = $row['parent'];
                $result['new_topics']++;

                continue;
            }

            if ($row['status'] === 'changed') {
                if (! $updateExisting) {
                    $result['skipped_conflicts']++;

                    continue;
                }

                $topic->forceFill(['name' => $row['name'], 'description' => $row['description'], 'public_key' => $row['public_key']])->save();
                $parentsToSet[$row['legacy_id']] = $row['parent'];
                $result['updated_topics']++;
            }
        }

        foreach ($parentsToSet as $legacyId => $parentLegacyId) {
            $topic = $byLegacy[$legacyId] ?? null;
            $parent = $parentLegacyId !== null ? ($byLegacy[$parentLegacyId] ?? null) : null;

            if ($topic !== null && $topic->parent_id !== $parent?->getKey()) {
                $topic->forceFill(['parent_id' => $parent?->getKey()])->save();
            }
        }

        $signatures = collect($plan->signatures)->sort(static fn (array $a, array $b): int => strnatcasecmp($a['signature'], $b['signature']))->values();
        $shelves = CatalogShelf::query()->get();
        $shelvesByCode = $shelves->keyBy('code');
        $shelvesByNormalized = $shelves->keyBy(static fn (CatalogShelf $shelf): string => CatalogShelf::normalizeCode($shelf->code));
        $order = (int) CatalogShelf::query()->max('sort_order');
        $touched = [];

        foreach ($signatures as $row) {
            $topicModels = array_values(array_filter(array_map(static fn (string $id): ?CatalogTopic => $byLegacy[$id] ?? null, $row['topics'])));

            $signature = CatalogSignature::query()->where('signature', $row['signature'])->first();

            if ($signature === null) {
                $signature = CatalogSignature::query()->create(['legacy_source' => LegacyCatalogNormalizer::SOURCE, 'legacy_id' => $row['legacy_id'], 'signature' => $row['signature']]);
                $result['new_signatures']++;
            } else {
                $result['existing_signatures']++;
            }

            $shelf = $shelvesByCode->get($row['signature']) ?? $shelvesByNormalized->get(CatalogShelf::normalizeCode($row['signature']));

            if ($shelf === null) {
                $label = mb_substr(collect($topicModels)->pluck('name')->implode(' / '), 0, 120);
                $shelf = CatalogShelf::query()->create([
                    'code' => $row['signature'],
                    'label' => $label !== '' ? $label : null,
                    'signature_id' => $signature->getKey(),
                    'sort_order' => ++$order,
                    'is_active' => true,
                ]);
                $shelvesByCode->put($shelf->code, $shelf);
                $shelvesByNormalized->put(CatalogShelf::normalizeCode($shelf->code), $shelf);
                $result['new_shelves']++;
            } else {
                $result['existing_shelves']++;

                if ($shelf->signature_id === null) {
                    $shelf->forceFill(['signature_id' => $signature->getKey()])->save();
                }
            }

            $result['new_assignments'] += $this->attachMissing($shelf->topics()->allRelatedIds()->all(), $topicModels, static fn (array $attach) => $shelf->topics()->attach($attach));
            $result['new_signature_assignments'] += $this->attachMissing($signature->topics()->allRelatedIds()->all(), $topicModels, static fn (array $attach) => $signature->topics()->attach($attach));

            $touched[$shelf->getKey()] = $shelf;
        }

        // Standortstruktur: nur für die berührten Regalbretter ohne Regal; vorhandene Zuordnungen bleiben.
        foreach ($touched as $shelf) {
            if ($shelf->section_id === null) {
                $this->structure->assign($shelf);
            }
        }

        $this->nameSections($touched);

        return $result;
    }

    /**
     * Hängt nur fehlende Themen an (Reihenfolge hinter den vorhandenen), ohne vorhandene Zuordnungen zu verändern.
     *
     * @param  list<string>  $existingIds
     * @param  list<CatalogTopic>  $topics
     * @param  callable(array<string, array{position: int}>): mixed  $attach
     */
    private function attachMissing(array $existingIds, array $topics, callable $attach): int
    {
        $position = count($existingIds);
        $missing = [];

        foreach ($topics as $topic) {
            if (! in_array((string) $topic->getKey(), array_map('strval', $existingIds), true)) {
                $missing[(string) $topic->getKey()] = ['position' => ++$position];
            }
        }

        if ($missing !== []) {
            $attach($missing);
        }

        return count($missing);
    }

    /**
     * Trägt die Namen der Bereichsgruppen und Bereiche ein, wo noch keiner steht.
     *
     * @param  array<string, CatalogShelf>  $shelves
     */
    private function nameSections(array $shelves): void
    {
        foreach ($shelves as $shelf) {
            $rack = $shelf->fresh()?->rack;
            $area = $rack?->parent;
            $group = $area?->parent;

            if (! $area instanceof CatalogShelfSection || ! $group instanceof CatalogShelfSection) {
                continue;
            }

            $groupCode = strtoupper(trim($group->code, '. '));
            $areaCode = strtoupper(trim($area->code));
            $definition = ClassificationImportPlanner::AREAS[$groupCode] ?? null;

            if ($definition === null) {
                continue;
            }

            if ($group->name === null || $group->name === '') {
                $group->forceFill(['name' => $definition['name']])->save();
            }

            $areaName = $definition['areas'][$areaCode] ?? null;

            if ($areaName !== null && ($area->name === null || $area->name === '')) {
                $area->forceFill(['name' => $areaName])->save();
            }
        }
    }
}
