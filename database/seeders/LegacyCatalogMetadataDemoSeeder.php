<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Models\CatalogSignature;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use Illuminate\Database\Seeder;
use RuntimeException;

final class LegacyCatalogMetadataDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('LegacyCatalogMetadataDemoSeeder darf nicht in production ausgeführt werden.');
        }

        $edition = Edition::query()->where('isbn', '9780544336261')->firstOrFail();
        $edition->forceFill([
            'responsibility_statement' => 'Lois Lowry',
            'series_statement' => 'BiblioCollect Metadaten-Demo',
            'publication_place' => 'Boston',
            'edition_number' => 'Demo-Metadatensatz',
            'alternate_identifiers' => ['DEMO-EAN-GIVER'],
            'original_language_code' => 'en',
            'page_count' => 240,
            'physical_extent' => '240 Seiten',
            'format_type' => 'Paperback',
            'summary' => 'Dieser Demo-Datensatz zeigt die erweiterten bibliografischen Metadaten ohne externe Live-Abfrage.',
            'subject_keywords' => 'Dystopie, Gesellschaft, Jugendbuch',
            'subject_keywords_system' => 'Demo-Schlagwortsystem',
            'target_audience' => 'Jugendliche',
            'age_recommendation' => ['minimum' => 12, 'label' => 'ab 12 Jahre'],
            'metadata_source' => 'demo',
            'source_record_id' => 'DEMO-RCN-GIVER',
            'source_permalink' => null,
        ])->save();

        $contributor = Contributor::query()
            ->where('display_name', 'Lois Lowry')
            ->firstOrFail();
        $contributor->forceFill(['gnd_id' => 'DEMO-GND-LOWRY'])->save();

        $root = CatalogTopic::query()->updateOrCreate(
            ['legacy_source' => 'demo', 'legacy_id' => 'topic-root'],
            [
                'public_key' => 'demo-root',
                'parent_id' => null,
                'name' => 'Kinder- und Jugendmedien',
                'description' => 'Demo-Wurzel für die hierarchische Klassifikation.',
            ],
        );
        $child = CatalogTopic::query()->updateOrCreate(
            ['legacy_source' => 'demo', 'legacy_id' => 'topic-dystopie'],
            [
                'public_key' => 'demo-dystopie',
                'parent_id' => $root->getKey(),
                'name' => 'Dystopie',
                'description' => 'Demo-Unterthema.',
            ],
        );

        $signature = CatalogSignature::query()->updateOrCreate(
            ['signature' => 'EN 7 LOWR'],
            [
                'legacy_source' => 'demo',
                'legacy_id' => 'signature-giver',
            ],
        );
        $signature->topics()->sync([
            (string) $root->getKey() => ['position' => 1],
            (string) $child->getKey() => ['position' => 2],
        ]);

        $copy = Copy::query()->where('barcode', 'BC-GIVER-001')->firstOrFail();
        $copy->forceFill([
            'signature_id' => $signature->getKey(),
            'access_status' => 'frei',
            'cataloged_on' => '2026-01-21',
            'condition_code' => 'neu',
            'internal_notes' => 'Demo für importierte/erweiterte Exemplarmetadaten.',
            'legacy_cover_path' => 'demo/cover-giver.jpg',
            'legacy_loan_count' => 3,
            'legacy_is_available' => false,
        ])->save();
    }
}
