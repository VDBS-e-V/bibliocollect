<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Series;
use App\Modules\Catalog\Services\SeriesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/** Reihen im Bestand prüfen: Zahl der Titel, Verlagsreihen ausblenden, Namen korrigieren, Zuordnung neu berechnen. */
final class CatalogSeriesController
{
    public function index(Request $request): Response
    {
        $term = trim((string) $request->query('q', ''));
        $query = Series::query()
            ->withCount(['editions as editions_count'])
            ->when($term !== '', static fn ($builder) => $builder->where('name', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%'))
            ->orderByDesc('editions_count')->orderBy('name');

        $statements = Edition::query()->whereNotNull('series_statement')->where('series_statement', '!=', '');

        return response()
            ->view('pages.surfaces.pos.catalog.series', [
                'series' => $query->paginate(50)->withQueryString(),
                'term' => $term,
                'totals' => [
                    'series' => Series::query()->count(),
                    'hidden' => Series::query()->where('is_hidden', true)->count(),
                    'withStatement' => (clone $statements)->count(),
                    'unassigned' => (clone $statements)->whereNull('series_id')->count(),
                    'withVolume' => Edition::query()->whereNotNull('series_volume')->count(),
                ],
                'unassignedSamples' => (clone $statements)->whereNull('series_id')->select('series_statement', DB::raw('count(*) as c'))->groupBy('series_statement')->orderByDesc('c')->limit(15)->get(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, string $seriesId, AuditRecorder $audit): RedirectResponse
    {
        $series = Series::query()->findOrFail($seriesId);
        $data = $request->validate(['name' => ['required', 'string', 'max:190']]);

        $series->update(['name' => trim($data['name']), 'is_hidden' => $request->boolean('is_hidden')]);
        $audit->record('catalog.series.updated', 'Reihe „'.$series->name.'“ geändert.', $series, ['hidden' => $series->is_hidden]);

        return back()->with('catalog_success', 'Die Reihe „'.$series->name.'“ ist gespeichert.');
    }

    public function sync(SeriesService $service): RedirectResponse
    {
        return back()->with('catalog_success', $service->syncAll().' Ausgaben neu zugeordnet.');
    }
}
