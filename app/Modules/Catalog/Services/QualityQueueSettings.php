<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Enums\MetadataIssue;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Was der nächtliche Lauf `catalog:quality:propose` zuerst abarbeiten soll: Problemarten, ob auch Anreicherung (Zusammenfassung,
 * Schlagwörter) dazugehört, und wie viele Fälle pro Nacht. Gespeichert als eine Zeile in `app_settings` (Schlüssel
 * `catalog.quality.queue`); die Seite „Regeln“ kennt diese Zeile nicht und lässt sie unberührt.
 */
final class QualityQueueSettings
{
    public const KEY = 'catalog.quality.queue';

    /** @return array{issues: list<string>, enrichment: bool, per_night: int} */
    public function get(): array
    {
        $default = ['issues' => [], 'enrichment' => false, 'per_night' => max(1, min(500, (int) config('catalog.quality.daily_proposals', 60)))];

        try {
            $json = DB::table('app_settings')->where('key', self::KEY)->value('value');
        } catch (Throwable) {
            return $default;
        }

        $data = is_string($json) ? json_decode($json, true) : null;

        if (! is_array($data)) {
            return $default;
        }

        $valid = array_map(static fn (MetadataIssue $issue): string => $issue->value, MetadataIssue::cases());

        return [
            'issues' => array_values(array_intersect($valid, array_map('strval', (array) ($data['issues'] ?? [])))),
            'enrichment' => (bool) ($data['enrichment'] ?? false),
            'per_night' => max(1, min(500, (int) ($data['per_night'] ?? $default['per_night']))),
        ];
    }

    /** @param  list<string>  $issues */
    public function save(array $issues, bool $enrichment, int $perNight, ?int $userId): void
    {
        $valid = array_map(static fn (MetadataIssue $issue): string => $issue->value, MetadataIssue::cases());

        DB::table('app_settings')->updateOrInsert(['key' => self::KEY], [
            'value' => json_encode([
                'issues' => array_values(array_intersect($valid, $issues)),
                'enrichment' => $enrichment,
                'per_night' => max(1, min(500, $perNight)),
            ], JSON_THROW_ON_ERROR),
            'updated_by_user_id' => $userId,
            'updated_at' => now(),
        ]);
    }
}
