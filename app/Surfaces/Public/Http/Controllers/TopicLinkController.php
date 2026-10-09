<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Modules\Catalog\Models\CatalogTopic;
use Illuminate\Http\RedirectResponse;

/** Ziel der QR-Codes „Thema“ auf den Regalbrett-Etiketten: führt in den Katalog, gefiltert auf das Thema (mit Regalbrettern und Medien). */
final class TopicLinkController
{
    public function __invoke(string $key): RedirectResponse
    {
        $wanted = CatalogTopic::normalizeKey($key);

        $topic = $wanted === '' ? null : CatalogTopic::query()->get(['id', 'name'])
            ->first(static fn (CatalogTopic $topic): bool => CatalogTopic::normalizeKey($topic->name) === $wanted);

        return $topic === null
            ? redirect()->route('public.catalog.index')
            : redirect()->route('public.catalog.index', ['thema' => $topic->name]);
    }
}
