<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\Audit\Queries\ListAuditEventsQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AuditIndexController
{
    public function __invoke(Request $request, ListAuditEventsQuery $events): Response
    {
        $area = $request->query('bereich');
        $term = $request->query('q');

        return response()
            ->view('pages.surfaces.administration.audit.index', [
                'events' => $events->paginate(
                    is_string($area) ? $area : null,
                    is_string($term) ? $term : null,
                ),
                'areas' => $events->areas(),
                'area' => is_string($area) ? $area : '',
                'term' => is_string($term) ? $term : '',
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
