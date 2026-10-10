<?php

declare(strict_types=1);

use App\Modules\Catalog\Support\Transliteration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lateinische Umschrift von Titeln und Namen in anderer Schrift, damit man sie mit lateinischen Buchstaben findet.
        Schema::table('catalog_titles', function (Blueprint $table): void {
            $table->string('search_aliases', 1000)->nullable();
        });

        Schema::table('catalog_contributors', function (Blueprint $table): void {
            $table->string('search_aliases', 1000)->nullable();
        });

        // Bestehende Datensätze nachtragen: nur die, die Zeichen außerhalb von ASCII enthalten.
        DB::table('catalog_titles')->orderBy('id')->select(['id', 'preferred_title', 'subtitle'])->chunk(500, function ($rows): void {
            foreach ($rows as $row) {
                $aliases = Transliteration::aliases($row->preferred_title, $row->subtitle);

                if ($aliases !== null) {
                    DB::table('catalog_titles')->where('id', $row->id)->update(['search_aliases' => $aliases]);
                }
            }
        });

        DB::table('catalog_contributors')->orderBy('id')->select(['id', 'display_name', 'sort_name'])->chunk(500, function ($rows): void {
            foreach ($rows as $row) {
                $aliases = Transliteration::aliases($row->display_name, $row->sort_name);

                if ($aliases !== null) {
                    DB::table('catalog_contributors')->where('id', $row->id)->update(['search_aliases' => $aliases]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalog_contributors', function (Blueprint $table): void {
            $table->dropColumn('search_aliases');
        });

        Schema::table('catalog_titles', function (Blueprint $table): void {
            $table->dropColumn('search_aliases');
        });
    }
};
