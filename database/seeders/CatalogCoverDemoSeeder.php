<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Models\Edition;
use Illuminate\Database\Seeder;
use RuntimeException;

final class CatalogCoverDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('CatalogCoverDemoSeeder darf nicht in production ausgeführt werden.');
        }

        $edition = Edition::query()->where('isbn', '9780544336261')->firstOrFail();

        if ($edition->getAttribute('cover_path') === null) {
            $edition->forceFill([
                'cover_status' => 'pending',
                'cover_source' => null,
                'cover_source_reference' => null,
                'cover_checked_at' => null,
                'cover_fetched_at' => null,
            ])->save();
        }
    }
}
