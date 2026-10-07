<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Exceptions\CatalogTaxonomyInUse;
use App\Modules\Catalog\Models\CatalogTopic;

/** Legt einen Themenbereich an oder ändert ihn. Ein Themenbereich kann nicht sein eigener Unterbereich werden. */
final readonly class SaveCatalogTopicAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(?CatalogTopic $topic, string $name, ?string $publicKey, ?string $parentId, ?string $description): CatalogTopic
    {
        $values = [
            'name' => trim($name),
            'public_key' => $publicKey !== null && trim($publicKey) !== '' ? trim($publicKey) : null,
            'parent_id' => $parentId !== null && $parentId !== '' ? $parentId : null,
            'description' => $description !== null && trim($description) !== '' ? trim($description) : null,
        ];

        if ($topic !== null && $values['parent_id'] !== null && $this->wouldCreateCycle($topic, $values['parent_id'])) {
            throw CatalogTaxonomyInUse::cycle();
        }

        if ($topic === null) {
            $topic = CatalogTopic::query()->create($values);
            $this->audit->record('catalog.topic.created', 'Themenbereich angelegt.', $topic);

            return $topic;
        }

        $topic->forceFill($values)->save();
        $this->audit->record('catalog.topic.updated', 'Themenbereich geändert.', $topic);

        return $topic;
    }

    private function wouldCreateCycle(CatalogTopic $topic, string $parentId): bool
    {
        $cursor = $parentId;
        $guard = 0;

        while ($cursor !== null && $guard++ < 20) {
            if ($cursor === (string) $topic->getKey()) {
                return true;
            }

            $cursor = CatalogTopic::query()->whereKey($cursor)->value('parent_id');
        }

        return false;
    }
}
