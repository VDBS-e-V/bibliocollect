<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\Catalog\Services\CatalogInventoryRenumberer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Alte Inventarnummern auf siebenstellige umstellen: nur auf ausdrücklichen Auftrag, nie automatisch. */
final class InventoryRenumberController
{
    private const LIMIT = 300;

    public function index(Request $request, CatalogInventoryRenumberer $renumberer): Response
    {
        $term = trim((string) $request->query('q', ''));
        $copies = $renumberer->legacyCopies($term);

        return response()
            ->view('pages.surfaces.administration.inventory.index', [
                'copies' => array_slice($copies, 0, self::LIMIT),
                'matching' => count($copies),
                'legacyTotal' => $renumberer->legacyCount(),
                'next' => str_pad((string) $renumberer->nextNumber(), 7, '0', STR_PAD_LEFT),
                'term' => $term,
                'limit' => self::LIMIT,
                'done' => session('renumbered', []),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, CatalogInventoryRenumberer $renumberer): RedirectResponse
    {
        $data = $request->validate([
            'copies' => ['required', 'array', 'min:1', 'max:'.self::LIMIT],
            'copies.*' => ['string', 'max:40'],
        ], ['copies.required' => 'Bitte mindestens ein Exemplar auswählen.']);

        $result = $renumberer->renumber(array_values($data['copies']));

        if ($result === []) {
            return redirect()->route('administration.inventory.index')->withErrors(['copies' => 'Die gewählten Exemplare haben schon siebenstellige Nummern.']);
        }

        return redirect()
            ->route('administration.inventory.index')
            ->with('renumbered', array_map(static fn (array $row): array => ['id' => (string) $row['copy']->getKey(), 'title' => $row['copy']->edition->title->preferred_title, 'old' => $row['old'], 'new' => $row['new']], $result));
    }
}
