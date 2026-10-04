<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Patrons\Queries\SearchPatronsQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PatronIndexController
{
    public function __invoke(Request $request, SearchPatronsQuery $search): Response
    {
        $term = trim((string) $request->query('q', ''));
        $patrons = $search->execute($term);

        return response()
            ->view('pages.surfaces.pos.patrons.index', [
                'term' => $term,
                'patrons' => $patrons,
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
