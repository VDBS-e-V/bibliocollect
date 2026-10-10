<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\Catalog\Enums\ShelfSectionKind;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogShelfSection;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Copy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Schreibfreie Übersicht über Belegung und Themenabdeckung der physischen Regalbretter. */
final class ShelfDashboardController
{
    public function index(Request $request): Response
    {
        return response()->view('pages.surfaces.administration.shelves.dashboard', $this->data($request))
            ->header('Cache-Control', 'private, no-store');
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->data($request);
        $rows = $data['rows'];

        return response()->streamDownload(static function () use ($rows): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Standort', 'Bezeichnung', 'Gruppe', 'Bereich', 'Regal', 'Exemplare', 'Kapazität', 'Themen', 'Hinweis'], ';');

            foreach ($rows as $row) {
                fputcsv($stream, [
                    $row['shelf']->code, $row['shelf']->label,
                    $row['group'], $row['area'], $row['rack'],
                    $row['copies'], $row['shelf']->capacity,
                    $row['topics'], $row['warning'],
                ], ';');
            }

            fclose($stream);
        }, 'bibliocollect-regal-dashboard.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array<string, mixed> */
    private function data(Request $request): array
    {
        $filters = $request->validate([
            'gruppe' => ['nullable', 'string', 'max:20'],
            'bereich' => ['nullable', 'string', 'max:20'],
            'regal' => ['nullable', 'string', 'max:20'],
            'ohne_thema' => ['nullable', 'boolean'],
        ]);

        $group = trim((string) ($filters['gruppe'] ?? ''));
        $area = trim((string) ($filters['bereich'] ?? ''));
        $rack = trim((string) ($filters['regal'] ?? ''));
        $withoutTopic = $request->boolean('ohne_thema');

        $shelves = CatalogShelf::query()
            ->with(['topics', 'rack.parent.parent'])
            ->orderBy('code')->get();

        $counts = Copy::query()
            ->whereNotNull('shelf_location')
            ->where('shelf_location', '!=', '')
            ->select('shelf_location')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('shelf_location')
            ->pluck('total', 'shelf_location');

        $rows = [];
        foreach ($shelves as $shelf) {
            $section = $shelf->rack;
            $areaSection = $section?->parent;
            $groupSection = $areaSection?->parent;

            if ($group !== '' && $groupSection?->code !== $group) {
                continue;
            }
            if ($area !== '' && $areaSection?->code !== $area) {
                continue;
            }
            if ($rack !== '' && $section?->code !== $rack) {
                continue;
            }
            if ($withoutTopic && $shelf->topics->isNotEmpty()) {
                continue;
            }

            $used = (int) ($counts[$shelf->code] ?? 0);
            $warnings = [];
            if ($shelf->topics->isEmpty()) {
                $warnings[] = 'Ohne Thema';
            }
            if ($section === null) {
                $warnings[] = 'Ohne Regalzuordnung';
            }
            if ($shelf->capacity !== null && $used > $shelf->capacity) {
                $warnings[] = 'Kapazität überschritten';
            }
            $rows[] = [
                'shelf' => $shelf,
                'group' => $groupSection?->code ?? '',
                'area' => $areaSection?->code ?? '',
                'rack' => $section?->code ?? '',
                'copies' => $used,
                'topics' => $shelf->topics->pluck('name')->implode(', '),
                'warning' => implode('; ', $warnings),
            ];
        }

        $validCodes = $shelves->pluck('code')->all();
        $withoutLocation = Copy::query()->where(function ($query): void {
            $query->whereNull('shelf_location')->orWhere('shelf_location', '');
        })->count();

        $unknownLocations = Copy::query()
            ->whereNotNull('shelf_location')->where('shelf_location', '!= '')
            ->when($validCodes !== [], static fn ($query) => $query->whereNotIn('shelf_location', $validCodes))
            ->count();

        $topicWithoutShelves = CatalogTopic::query()->whereDoesntHave('shelves')->count();

        return [
            'rows' => $rows,
            'groupOptions' => CatalogShelfSection::query()->where('kind', ShelfSectionKind::Group->value)->orderBy('code')->pluck('code')->all(),
            'group' => $group, 'area' => $area, 'rack' => $rack, 'withoutTopic' => $withoutTopic,
            'total' => count($rows),
            'used' => array_sum(array_column($rows, 'copies')),
            'withoutTopicCount' => count(array_filter($rows, static fn (array $row): bool => $row['shelf']->topics->isEmpty())),
            'missingCapacity' => count(array_filter($rows, static fn (array $row): bool => $row['shelf']->capacity === null)),
            'withoutLocation' => $withoutLocation,
            'unknownLocations' => $unknownLocations,
            'topicWithoutShelves' => $topicWithoutShelves,
        ];
    }
}
