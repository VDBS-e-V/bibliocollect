<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Modules\Catalog\Models\CatalogShelf;
use Illuminate\Http\RedirectResponse;

/** Ziel der QR-Codes auf den Regalbrett-Etiketten: führt in den Katalog, gefiltert auf die Medien dieses Bretts. */
final class ShelfLinkController
{
    public function __invoke(string $code): RedirectResponse
    {
        $wanted = CatalogShelf::normalizeCode($code);

        $shelf = $wanted === '' ? null : CatalogShelf::query()->get(['id', 'code'])
            ->first(static fn (CatalogShelf $shelf): bool => CatalogShelf::normalizeCode($shelf->code) === $wanted);

        return $shelf === null
            ? redirect()->route('public.catalog.index')
            : redirect()->route('public.catalog.index', ['regalbrett' => $shelf->code]);
    }
}
