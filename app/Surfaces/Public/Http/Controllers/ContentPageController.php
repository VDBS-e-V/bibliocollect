<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Modules\Content\Models\ContentPage;
use App\Modules\Content\Services\PageContentRenderer;
use Illuminate\Http\Response;

/** Öffentliche Informationsseiten (Impressum, Datenschutz, Barrierefreiheit). */
final class ContentPageController
{
    public function __invoke(string $slug, PageContentRenderer $renderer): Response
    {
        $page = ContentPage::query()->where('slug', $slug)->firstOrFail();

        return response()->view('pages.surfaces.public.content-page', [
            'page' => $page,
            'html' => $renderer->toHtml($page->body),
        ]);
    }
}
