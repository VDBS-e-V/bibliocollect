<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Content\Models\ContentPage;
use App\Modules\Content\Services\PageTextRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ContentPageAdminController
{
    public function index(): Response
    {
        return response()
            ->view('pages.surfaces.administration.pages.index', ['pages' => ContentPage::query()->orderBy('title')->get()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function edit(string $slug, PageTextRenderer $renderer): Response
    {
        $page = ContentPage::query()->where('slug', $slug)->firstOrFail();

        return response()
            ->view('pages.surfaces.administration.pages.edit', ['page' => $page, 'preview' => $renderer->render($page->body)])
            ->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, string $slug, AuditRecorder $audit): RedirectResponse
    {
        $page = ContentPage::query()->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:60000'],
        ]);

        $page->forceFill([
            'title' => trim($data['title']),
            'body' => trim($data['body']),
            'is_placeholder' => false,
            'updated_by_user_id' => $request->user()?->getKey(),
        ])->save();

        $audit->record('content.page.updated', 'Seite „'.$page->title.'“ geändert.', $page, ['slug' => $page->slug]);

        return redirect()
            ->route('administration.pages.edit', ['slug' => $page->slug])
            ->with('school_success', 'Die Seite wurde gespeichert.');
    }
}
