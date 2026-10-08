<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Standortstruktur: Bereichsgruppe (I) › Bereich (A) › Regal (1) › Regalbrett (a). Aus den vorhandenen Codes der Form
     * „I. A 1 a“ werden die Einträge einmalig angelegt; andere Codes bleiben ohne Zuordnung.
     */
    public function up(): void
    {
        Schema::create('catalog_shelf_sections', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('parent_id')->nullable()->constrained('catalog_shelf_sections')->restrictOnDelete();
            $table->string('kind', 10);
            $table->string('code', 20);
            $table->string('name', 120)->nullable();
            $table->string('description', 300)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['kind', 'parent_id']);
        });

        Schema::table('catalog_shelves', function (Blueprint $table): void {
            $table->foreignUlid('section_id')->nullable()->after('label')->constrained('catalog_shelf_sections')->nullOnDelete();
            $table->string('board', 20)->nullable()->after('section_id');
        });

        $nodes = [];
        $now = now();

        $node = static function (string $kind, ?string $parentId, string $code) use (&$nodes, $now): string {
            $key = $kind.'|'.$parentId.'|'.mb_strtolower($code);

            if (! isset($nodes[$key])) {
                $nodes[$key] = (string) Str::ulid();
                DB::table('catalog_shelf_sections')->insert(['id' => $nodes[$key], 'parent_id' => $parentId, 'kind' => $kind, 'code' => $code, 'sort_order' => count($nodes), 'created_at' => $now, 'updated_at' => $now]);
            }

            return $nodes[$key];
        };

        foreach (DB::table('catalog_shelves')->orderBy('sort_order')->orderBy('code')->get(['id', 'code']) as $shelf) {
            if (preg_match('/^\s*([IVXLCDM]+|\d+)\.?\s+([A-Za-z]+)\s+(\d+)\s*([A-Za-z])\s*$/i', (string) $shelf->code, $m) !== 1) {
                continue;
            }

            $group = $node('group', null, $m[1]);
            $area = $node('area', $group, $m[2]);
            $rack = $node('rack', $area, $m[3]);

            DB::table('catalog_shelves')->where('id', $shelf->id)->update(['section_id' => $rack, 'board' => $m[4]]);
        }
    }

    public function down(): void
    {
        Schema::table('catalog_shelves', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('section_id');
            $table->dropColumn('board');
        });

        Schema::dropIfExists('catalog_shelf_sections');
    }
};
