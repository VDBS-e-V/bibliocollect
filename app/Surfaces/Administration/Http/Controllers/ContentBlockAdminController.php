<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Content\Models\ContentBlock;
use App\Modules\Content\Services\ContentBlocks;
use App\Modules\Content\Services\PageContentRenderer;
use App\Modules\Content\Services\RichTextSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Textbausteine (Hinweise auf Startseite und Katalog) bearbeiten, ein- und ausschalten, befristen. */
final class ContentBlockAdminController
{
    public function index(ContentBlocks $blocks): Response
    {
        $stored = ContentBlock::query()->get()->keyBy('key');
        $rows = [];

        foreach ($blocks->definitions() as $key => $definition) {
            $block = $stored->get($key);
            $rows[] = $definition + [
                'key' => $key,
                'block' => $block,
                'visible' => $block instanceof ContentBlock && $block->isVisibleOn(now()),
            ];
        }

        return response()->view('pages.surfaces.administration.blocks.index', ['rows' => $rows])->header('Cache-Control', 'private, no-store');
    }

    public function edit(string $key, ContentBlocks $blocks, PageContentRenderer $renderer): Response
    {
        $definition = $blocks->definitions()[$key] ?? null;
        abort_if($definition === null, 404);

        $block = ContentBlock::query()->where('key', $key)->first();

        return response()
            ->view('pages.surfaces.administration.blocks.edit', [
                'key' => $key,
                'definition' => $definition,
                'block' => $block,
                'editorHtml' => $block instanceof ContentBlock ? $renderer->toHtml($block->body) : '',
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, string $key, ContentBlocks $blocks, PageContentRenderer $renderer, RichTextSanitizer $sanitizer, AuditRecorder $audit): RedirectResponse
    {
        $definition = $blocks->definitions()[$key] ?? null;
        abort_if($definition === null, 404);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:20000'],
            'visible_from' => ['nullable', 'date'],
            'visible_until' => ['nullable', 'date', 'after_or_equal:visible_from'],
        ]);

        $html = $renderer->toHtml((string) ($data['body'] ?? ''));
        $active = $request->boolean('is_active');

        if ($active && ! $sanitizer->hasText($html)) {
            return back()->withInput()->withErrors(['body' => 'Zum Einschalten braucht der Baustein einen Text.']);
        }

        $block = ContentBlock::query()->firstOrNew(['key' => $key]);
        $block->forceFill([
            'body' => $html,
            'is_active' => $active,
            'visible_from' => ($data['visible_from'] ?? null) ?: null,
            'visible_until' => ($data['visible_until'] ?? null) ?: null,
            'updated_by_user_id' => $request->user()?->getKey(),
        ])->save();

        $blocks->forget($key);
        $audit->record('content.block.updated', 'Textbaustein „'.$definition['title'].'“ geändert.', $block, ['key' => $key, 'active' => $active]);

        return redirect()->route('administration.blocks.edit', ['key' => $key])->with('school_success', 'Der Textbaustein wurde gespeichert.');
    }
}
