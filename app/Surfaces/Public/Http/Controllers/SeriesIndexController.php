<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Series;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/** Alle sichtbaren Reihen mit mindestens zwei Titeln im Bestand. */
final class SeriesIndexController
{
    public function __invoke(): Response
    {
        $counts = DB::table('catalog_editions')
            ->join('catalog_series', 'catalog_series.id', '=', 'catalog_editions.series_id')
            ->where('catalog_series.is_hidden', false)
            ->whereExists(static function ($copies): void {
                $copies->selectRaw('1')->from('catalog_copies')
                    ->whereColumn('catalog_copies.edition_id', 'catalog_editions.id')
                    ->whereIn('catalog_copies.status', [CopyStatus::Active->value, CopyStatus::Damaged->value]);
            })
            ->groupBy('catalog_series.id')
            ->havingRaw('count(distinct catalog_editions.title_id) >= 2')
            ->selectRaw('catalog_series.id as id, count(distinct catalog_editions.title_id) as titles')
            ->pluck('titles', 'id');

        $series = Series::query()->whereIn('id', $counts->keys())->orderBy('name')->get();

        return response()->view('pages.surfaces.public.series-index', [
            'series' => $series->map(static fn (Series $item): array => ['name' => $item->name, 'slug' => $item->slug, 'titles' => (int) $counts[$item->getKey()]])->all(),
        ]);
    }
}
