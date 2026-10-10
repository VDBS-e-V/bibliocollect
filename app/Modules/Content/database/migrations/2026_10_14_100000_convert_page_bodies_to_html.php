<?php

declare(strict_types=1);

use App\Modules\Content\Services\PageContentRenderer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mit dem Texteditor ist HTML das Speicherformat der Informationsseiten. Bestehende Texte im alten Format (einfacher Text mit
     * „## Überschrift“ und „- Liste“) werden einmalig umgewandelt; was schon HTML ist, bleibt unverändert.
     */
    public function up(): void
    {
        $renderer = app(PageContentRenderer::class);

        DB::table('content_pages')->orderBy('id')->select(['id', 'body'])->get()->each(static function ($row) use ($renderer): void {
            $body = (string) $row->body;

            if ($renderer->looksLikeHtml($body)) {
                return;
            }

            DB::table('content_pages')->where('id', $row->id)->update(['body' => $renderer->toHtml($body)]);
        });
    }

    public function down(): void {}
};
