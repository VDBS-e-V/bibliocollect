<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Exceptions\CatalogTaxonomyInUse;
use App\Modules\Catalog\Models\CatalogTopic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class DeleteCatalogTopicAction
{
    public const CHILDREN_BLOCK = 'block';

    /** Unterbereiche rücken eine Ebene nach oben (unter den Elternbereich dieses Themas). */
    public const CHILDREN_MOVE = 'move';

    /** Unterbereiche werden mit gelöscht. */
    public const CHILDREN_DELETE = 'delete';

    public function __construct(private AuditRecorder $audit) {}

    /**
     * Löscht den Themenbereich. Ohne ausdrückliche Wahl bleibt es dabei: mit Unterbereichen oder Regalbrett-Zuordnungen geht es nicht.
     * Medien sind davon nicht betroffen (ihre Zuordnung läuft über Regalbrett und Klassifikation, nicht über eine feste Verknüpfung).
     *
     * @param  string  $children  block | move | delete
     * @param  bool  $detachShelves  Zuordnungen zu Regalbrettern lösen
     * @return array{deleted: int, moved: int}
     *
     * @throws CatalogTaxonomyInUse
     */
    public function execute(CatalogTopic $topic, string $children = self::CHILDREN_BLOCK, bool $detachShelves = false): array
    {
        return DB::transaction(function () use ($topic, $children, $detachShelves): array {
            $childCount = $topic->children()->count();
            $family = $topic->family();
            $targets = $children === self::CHILDREN_DELETE ? $family : collect([$topic]);

            $shelfCount = DB::table('catalog_shelf_topics')->whereIn('topic_id', $targets->pluck('id'))->count();

            if (($childCount > 0 && $children === self::CHILDREN_BLOCK) || ($shelfCount > 0 && ! $detachShelves)) {
                throw CatalogTaxonomyInUse::topic($topic->name, $childCount, $topic->shelves()->count());
            }

            $moved = 0;

            if ($childCount > 0 && $children === self::CHILDREN_MOVE) {
                $moved = CatalogTopic::query()->where('parent_id', $topic->getKey())->update(['parent_id' => $topic->parent_id]);
            }

            $ids = $targets->pluck('id')->all();

            DB::table('catalog_shelf_topics')->whereIn('topic_id', $ids)->delete();
            DB::table('catalog_signature_topics')->whereIn('topic_id', $ids)->delete();

            $this->audit->record('catalog.topic.deleted', 'Themenbereich gelöscht.', $topic, [
                'name' => $topic->name,
                'deleted' => count($ids),
                'children_moved' => $moved,
                'shelf_links_removed' => $shelfCount,
            ]);

            // Tiefste Ebene zuerst, damit kein Elternverweis ins Leere läuft.
            foreach ($targets->sortByDesc(fn (CatalogTopic $item): int => $this->depth($item, $family))->all() as $item) {
                $item->delete();
            }

            return ['deleted' => count($ids), 'moved' => $moved];
        });
    }

    /** @param  Collection<int, CatalogTopic>  $family */
    private function depth(CatalogTopic $topic, $family): int
    {
        $depth = 0;
        $byId = $family->keyBy('id');
        $node = $topic;

        while ($node->parent_id !== null && $byId->has($node->parent_id) && $depth < 10) {
            $node = $byId->get($node->parent_id);
            $depth++;
        }

        return $depth;
    }
}
