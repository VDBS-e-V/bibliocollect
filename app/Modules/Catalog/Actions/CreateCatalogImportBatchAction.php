<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Contracts\CatalogImportSource;
use App\Modules\Catalog\Enums\CatalogImportRowStatus;
use App\Modules\Catalog\Enums\CatalogImportStatus;
use App\Modules\Catalog\Exceptions\CatalogImportSourceException;
use App\Modules\Catalog\Import\CatalogImportMappingSuggester;
use App\Modules\Catalog\Models\CatalogImportBatch;
use Illuminate\Support\Facades\DB;

final class CreateCatalogImportBatchAction
{
    public function __construct(private readonly CatalogImportMappingSuggester $mappingSuggester) {}

    public function execute(
        CatalogImportSource $source,
        ?int $createdByUserId,
        ?string $originalFilename,
    ): CatalogImportBatch {
        return DB::transaction(function () use ($source, $createdByUserId, $originalFilename): CatalogImportBatch {
            $headers = $source->headers();

            $batch = CatalogImportBatch::query()->create([
                'created_by_user_id' => $createdByUserId,
                'source_format' => $source->format(),
                'original_filename' => $originalFilename,
                'status' => CatalogImportStatus::Uploaded,
                'headers' => $headers,
                'mapping' => $this->mappingSuggester->suggest($headers)->toArray(),
                'summary' => null,
                'committed_at' => null,
            ]);

            $rowCount = 0;

            foreach ($source->records() as $record) {
                $rowCount++;
                $batch->rows()->create([
                    'row_number' => $record->rowNumber,
                    'status' => CatalogImportRowStatus::Pending,
                    'raw_data' => $record->values,
                    'source_errors' => $record->errors,
                    'normalized_data' => null,
                    'plan' => null,
                    'warnings' => [],
                    'conflicts' => [],
                ]);
            }

            if ($rowCount === 0) {
                throw new CatalogImportSourceException('Die CSV-Datei enthält keine Datenzeilen.');
            }

            return $batch->load('rows');
        });
    }
}
