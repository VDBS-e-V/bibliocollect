<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Series;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Support\SeriesParser;
use App\Modules\Circulation\Models\Bookmark;
use App\Modules\Circulation\Models\Loan;
use App\Surfaces\Public\Support\ReadingListPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Öffentliche Seite einer Reihe: alle Bände nach Bandnummer mit Verfügbarkeit; angemeldete Leser:innen sehen, welcher Band als Nächstes dran ist. */
final class SeriesController
{
    public function __invoke(Request $request, string $slug, ReadingListPresenter $presenter): Response
    {
        $series = Series::query()->where('slug', $slug)->where('is_hidden', false)->firstOrFail();

        $editions = Edition::query()
            ->where('series_id', $series->getKey())
            ->whereHas('copies', static fn ($copies) => $copies->whereIn('status', [CopyStatus::Active->value, CopyStatus::Damaged->value]))
            ->get(['id', 'title_id', 'series_volume']);

        abort_if($editions->isEmpty(), 404);

        // Je Titel der niedrigste Band; Titel ohne Nummer stehen hinten, alphabetisch.
        $volumes = [];

        foreach ($editions as $edition) {
            $id = (string) $edition->title_id;
            $number = SeriesParser::volumeNumber($edition->series_volume);
            $current = $volumes[$id] ?? null;

            if ($current === null || ($number !== null && ($current['number'] === null || $number < $current['number']))) {
                $volumes[$id] = ['number' => $number, 'label' => $edition->series_volume];
            }
        }

        $sortTitles = Title::query()->whereIn('id', array_keys($volumes))->pluck('sort_title', 'id');
        $ids = array_keys($volumes);
        usort($ids, static function (string $a, string $b) use ($volumes, $sortTitles): int {
            return [$volumes[$a]['number'] ?? PHP_INT_MAX, mb_strtolower((string) $sortTitles[$a])] <=> [$volumes[$b]['number'] ?? PHP_INT_MAX, mb_strtolower((string) $sortTitles[$b])];
        });

        $rows = $presenter->rows($ids);
        $user = $request->user();

        return response()->view('pages.surfaces.public.series', [
            'series' => $series,
            'rows' => array_map(static fn (array $row): array => $row + ['volume' => $volumes[$row['id']]['label'] ?? null], $rows),
            'next' => $this->nextTitle($user instanceof User ? $user : null, $series, $volumes, $rows),
            'marked' => $user instanceof User ? Bookmark::markedBy((int) $user->getKey(), array_column($rows, 'id')) : [],
        ]);
    }

    /**
     * Der Band nach dem höchsten schon ausgeliehenen (nur mit Konto): „Nächster Band“.
     *
     * @param  array<string, array{number: ?int, label: ?string}>  $volumes
     * @param  list<array{id: string, title: string, authors: string, cover: string, badge: string, variant: string}>  $rows
     * @return array{id: string, title: string, volume: ?string}|null
     */
    private function nextTitle(?User $user, Series $series, array $volumes, array $rows): ?array
    {
        if ($user === null || $user->patron_id === null) {
            return null;
        }

        $borrowed = Loan::query()
            ->where('patron_id', $user->patron_id)
            ->join('catalog_copies', 'catalog_copies.id', '=', 'circulation_loans.copy_id')
            ->join('catalog_editions', 'catalog_editions.id', '=', 'catalog_copies.edition_id')
            ->where('catalog_editions.series_id', $series->getKey())
            ->pluck('catalog_editions.series_volume')
            ->map(static fn ($volume): ?int => SeriesParser::volumeNumber(is_string($volume) ? $volume : null))
            ->filter()
            ->max();

        if ($borrowed === null) {
            return null;
        }

        foreach ($rows as $row) {
            $number = $volumes[$row['id']]['number'] ?? null;

            if ($number !== null && $number > $borrowed) {
                return ['id' => $row['id'], 'title' => $row['title'], 'volume' => $volumes[$row['id']]['label']];
            }
        }

        return null;
    }
}
