<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Catalog\Queries\SearchCatalogTitlesQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CatalogIndexController
{
    public function __invoke(Request $request, SearchCatalogTitlesQuery $search): Response
    {
        $term = trim((string) $request->query('q', ''));

        return response()
            ->view('pages.surfaces.pos.catalog.index', [
                'term' => $term,
                'titles' => $search->execute($term),
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
