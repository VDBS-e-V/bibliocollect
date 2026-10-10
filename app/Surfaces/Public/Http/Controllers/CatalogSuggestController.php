<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Modules\Catalog\Services\SearchSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Vorschläge beim Tippen im Suchfeld (Titel und Namen); kurze Liste als JSON. */
final class CatalogSuggestController
{
    public function __invoke(Request $request, SearchSuggestionService $suggestions): JsonResponse
    {
        $term = mb_substr(trim((string) $request->query('q', '')), 0, 60);

        return response()->json(['suggestions' => $suggestions->complete($term)])->header('Cache-Control', 'public, max-age=60');
    }
}
