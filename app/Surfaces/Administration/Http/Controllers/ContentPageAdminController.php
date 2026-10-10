<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Content\Models\ContentPage;
use App\Modules\Content\Services\PageContentRenderer;
use App\Modules\Content\Services\RichTextSanitizer;
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

    public function edit(string $slug, PageContentRenderer $renderer): Response
    {
        $page = ContentPage::query()->where('slug', $slug)->firstOrFail();
        $html = $renderer->toHtml($page->body);

        return response()
            ->view('pages.surfaces.administration.pages.edit', ['page' => $page, 'preview' => $html, 'editorHtml' => $html])
            ->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, string $slug, AuditRecorder $audit, PageContentRenderer $renderer, RichTextSanitizer $sanitizer): RedirectResponse
    {
        $page = ContentPage::query()->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:120000'],
        ]);

        // Gespeichert wird bereinigtes HTML; Text im alten Format wird dabei umgewandelt.
        $html = $renderer->toHtml($data['body']);

        if (! $sanitizer->hasText($html)) {
            return back()->withInput()->withErrors(['body' => 'Der Text ist nach dem Bereinigen leer. Bitte gib einen Text ein.']);
        }

        $page->forceFill([
            'title' => trim($data['title']),
            'body' => $html,
            'is_placeholder' => false,
            'updated_by_user_id' => $request->user()?->getKey(),
        ])->save();

        $audit->record('content.page.updated', 'Seite „'.$page->title.'“ geändert.', $page, ['slug' => $page->slug]);

        return redirect()
            ->route('administration.pages.edit', ['slug' => $page->slug])
            ->with('school_success', 'Die Seite wurde gespeichert.');
    }
}
