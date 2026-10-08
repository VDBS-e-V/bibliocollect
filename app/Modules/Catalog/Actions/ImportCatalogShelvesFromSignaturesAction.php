<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogSignature;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Services\CatalogShelfStructure;
use Illuminate\Support\Facades\DB;

/**
 * Übernimmt die Signaturen des Altsystems als Regalbretter (Bezeichnung = Signatur, Beschriftung = die Themenbereiche) samt
 * Themenbereichen für den Vorschlag beim Einsortieren. Wiederholbar: Vorhandene Regalbretter behalten Reihenfolge und Schalter. Alte Freitext-Standorte,
 * die nur anders geschrieben sind („IA1d“ statt „I. A 1 d“), werden dem Regalbrett zugeordnet und doppelte Einträge entfernt.
 */
final readonly class ImportCatalogShelvesFromSignaturesAction
{
    public function __construct(private AuditRecorder $audit, private CatalogShelfStructure $structure) {}

    /** @return array{created: int, updated: int, merged: int} */
    public function execute(): array
    {
        return DB::transaction(function (): array {
            $created = 0;
            $updated = 0;
            $merged = 0;

            $signatures = CatalogSignature::query()->with('topics')->get()->sort(static fn (CatalogSignature $a, CatalogSignature $b): int => strnatcasecmp($a->signature, $b->signature))->values();
            $order = (int) CatalogShelf::query()->max('sort_order');

            foreach ($signatures as $signature) {
                $code = mb_substr(trim($signature->signature), 0, 40);
                $label = mb_substr($signature->topics->pluck('name')->implode(' / '), 0, 120);
                $label = $label !== '' ? $label : null;

                $shelf = CatalogShelf::query()->where('code', $code)->first();

                if ($shelf === null) {
                    $shelf = CatalogShelf::query()->create(['code' => $code, 'label' => $label, 'signature_id' => $signature->getKey(), 'sort_order' => ++$order, 'is_active' => true]);
                    $created++;
                } else {
                    $shelf->forceFill(['label' => $shelf->label ?? $label, 'signature_id' => $signature->getKey()])->save();
                    $updated++;
                }

                $shelf->topics()->syncWithoutDetaching($signature->topics->values()->mapWithKeys(static fn ($topic, int $index): array => [(string) $topic->getKey() => ['position' => $index + 1]])->all());

                // Gleich geschriebene Standorte ohne Punkte, Leerzeichen und Groß-/Kleinschreibung zusammenführen.
                foreach (CatalogShelf::query()->where('id', '!=', $shelf->getKey())->get() as $other) {
                    if ($this->normalize($other->code) === $this->normalize($code)) {
                        Copy::query()->where('shelf_location', $other->code)->update(['shelf_location' => $code]);
                        $other->delete();
                        $merged++;
                    }
                }
            }

            $this->structure->assignAll();

            $this->audit->record('catalog.shelf.imported', 'Regalbretter aus den Signaturen übernommen.', null, ['created' => $created, 'updated' => $updated, 'merged' => $merged]);

            return ['created' => $created, 'updated' => $updated, 'merged' => $merged];
        });
    }

    private function normalize(string $value): string
    {
        return mb_strtolower((string) preg_replace('/[^\p{L}\p{N}]/u', '', $value));
    }
}
