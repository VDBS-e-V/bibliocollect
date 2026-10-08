<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Catalog\Models\Copy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Etiketten für Exemplare: Strichcode, Standort und Kurztitel auf Bögen mit 3 × 8 Etiketten (70 × 36 mm). */
final class CopyLabelController
{
    public const PER_SHEET = 24;

    public function index(Request $request): Response
    {
        $term = trim((string) $request->query('q', ''));
        $recent = $request->boolean('neu');
        $editionId = (string) $request->query('ausgabe', '');

        $copies = Copy::query()
            ->with('edition.title')
            ->when($editionId !== '', static fn ($query) => $query->where('edition_id', $editionId))
            ->when($term !== '', static function ($query) use ($term): void {
                $like = '%'.$term.'%';
                $query->where(static function ($inner) use ($like): void {
                    $inner->where('barcode', 'like', $like)
                        ->orWhereHas('edition', static fn ($edition) => $edition->where('isbn', 'like', $like)
                            ->orWhereHas('title', static fn ($title) => $title->where('preferred_title', 'like', $like)));
                });
            })
            ->when($recent, static fn ($query) => $query->orderByDesc('created_at'), static fn ($query) => $query->orderBy('barcode'))
            ->limit($recent ? 48 : 100)
            ->get();

        return response()
            ->view('pages.surfaces.pos.labels.copies-index', [
                'copies' => $copies,
                'term' => $term,
                'recent' => $recent,
                'editionId' => $editionId,
                'perSheet' => self::PER_SHEET,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function print(Request $request): Response
    {
        $data = $request->validate([
            'copies' => ['required', 'array', 'min:1', 'max:300'],
            'copies.*' => ['string', 'max:40'],
            'start' => ['nullable', 'integer', 'between:1,'.self::PER_SHEET],
        ], ['copies.required' => 'Bitte mindestens ein Exemplar auswählen.']);

        $copies = Copy::query()
            ->with('edition.title')
            ->whereIn('id', $data['copies'])
            ->orderBy('barcode')
            ->get();

        return response()
            ->view('pages.surfaces.pos.labels.copies-print', [
                'copies' => $copies,
                'skip' => max(0, ((int) ($data['start'] ?? 1)) - 1),
                'perSheet' => self::PER_SHEET,
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
