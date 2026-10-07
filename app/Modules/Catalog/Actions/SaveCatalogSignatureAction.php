<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Models\CatalogSignature;
use Illuminate\Support\Facades\DB;

/** Legt eine Signatur an oder ändert sie, samt der Themenbereiche, die sie zusammenfasst (in der gewählten Reihenfolge). */
final readonly class SaveCatalogSignatureAction
{
    public function __construct(private AuditRecorder $audit) {}

    /** @param list<string> $topicIds */
    public function execute(?CatalogSignature $signature, string $code, array $topicIds): CatalogSignature
    {
        return DB::transaction(function () use ($signature, $code, $topicIds): CatalogSignature {
            $code = trim($code);

            if ($signature === null) {
                $signature = CatalogSignature::query()->create(['signature' => $code]);
                $this->audit->record('catalog.signature.created', 'Signatur angelegt.', $signature);
            } else {
                $signature->forceFill(['signature' => $code])->save();
                $this->audit->record('catalog.signature.updated', 'Signatur geändert.', $signature);
            }

            $positions = [];

            foreach (array_values(array_unique($topicIds)) as $index => $topicId) {
                $positions[$topicId] = ['position' => $index + 1];
            }

            $signature->topics()->sync($positions);

            return $signature;
        });
    }
}
