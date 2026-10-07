<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ersetzt die kurzen Platzhaltertexte durch ausführliche Entwürfe (resources/content/legal). Nur Seiten, die noch
     * Platzhalter sind, werden angefasst; was jemand schon bearbeitet hat, bleibt unverändert. Die Seiten bleiben
     * „Platzhalter“, bis die Angaben in eckigen Klammern ausgefüllt und gespeichert sind.
     */
    public function up(): void
    {
        foreach (['impressum', 'datenschutz', 'barrierefreiheit'] as $slug) {
            $path = resource_path('content/legal/'.$slug.'.txt');

            if (! is_file($path)) {
                continue;
            }

            DB::table('content_pages')
                ->where('slug', $slug)
                ->where('is_placeholder', true)
                ->update(['body' => trim((string) file_get_contents($path)), 'updated_at' => now()]);
        }
    }

    public function down(): void {}
};
