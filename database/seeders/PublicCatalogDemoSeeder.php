<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use Illuminate\Database\Seeder;
use RuntimeException;

final class PublicCatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('PublicCatalogDemoSeeder darf nicht in production ausgeführt werden.');
        }

        $this->seedEnglishTitleWithActiveHolding();
        $this->seedTitleWithoutPhysicalHoldings();
    }

    private function seedEnglishTitleWithActiveHolding(): void
    {
        $title = Title::query()->updateOrCreate(
            ['preferred_title' => 'The Giver'],
            [
                'subtitle' => null,
                'sort_title' => 'Giver',
            ],
        );

        $contributor = Contributor::query()->firstOrCreate(
            ['display_name' => 'Lois Lowry'],
            ['sort_name' => 'Lowry, Lois'],
        );

        if ($contributor->sort_name !== 'Lowry, Lois') {
            $contributor->forceFill(['sort_name' => 'Lowry, Lois'])->save();
        }

        TitleContribution::query()->updateOrCreate(
            [
                'title_id' => $title->getKey(),
                'contributor_id' => $contributor->getKey(),
                'role_key' => 'author',
            ],
            ['position' => 1],
        );

        $edition = Edition::query()->updateOrCreate(
            [
                'title_id' => $title->getKey(),
                'isbn' => '9780544336261',
            ],
            [
                'edition_statement' => 'Paperback edition',
                'publisher_name' => 'Clarion Books',
                'publication_year' => 2014,
                'media_type' => 'book',
                'language_code' => 'en',
                'minimum_age' => 12,
                'age_rating_label' => 'ab 12',
            ],
        );

        Copy::query()->updateOrCreate(
            ['barcode' => 'BC-GIVER-001'],
            [
                'edition_id' => $edition->getKey(),
                'status' => CopyStatus::Active,
                'shelf_location' => 'EN 7 LOWR',
            ],
        );
    }

    private function seedTitleWithoutPhysicalHoldings(): void
    {
        $title = Title::query()->updateOrCreate(
            ['preferred_title' => 'Die Welle'],
            [
                'subtitle' => null,
                'sort_title' => 'Welle',
            ],
        );

        $contributor = Contributor::query()->firstOrCreate(
            ['display_name' => 'Morton Rhue'],
            ['sort_name' => 'Rhue, Morton'],
        );

        if ($contributor->sort_name !== 'Rhue, Morton') {
            $contributor->forceFill(['sort_name' => 'Rhue, Morton'])->save();
        }

        TitleContribution::query()->updateOrCreate(
            [
                'title_id' => $title->getKey(),
                'contributor_id' => $contributor->getKey(),
                'role_key' => 'author',
            ],
            ['position' => 1],
        );

        Edition::query()->updateOrCreate(
            [
                'title_id' => $title->getKey(),
                'isbn' => '9783473580081',
            ],
            [
                'edition_statement' => 'Schulausgabe',
                'publisher_name' => 'Ravensburger',
                'publication_year' => 2020,
                'media_type' => 'book',
                'language_code' => 'de',
                'minimum_age' => 12,
                'age_rating_label' => 'ab 12',
            ],
        );
    }
}
