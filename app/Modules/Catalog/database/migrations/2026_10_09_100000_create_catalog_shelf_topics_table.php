<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ein Regalbrett gehört zu einem oder mehreren Themenbereichen, ein Themenbereich kann auf mehreren Regalbrettern stehen.
     * Daraus entsteht beim Einsortieren der Vorschlag. Bisher lief diese Verbindung über die Signatur des Regalbretts; sie wird
     * hier einmalig übernommen.
     */
    public function up(): void
    {
        Schema::create('catalog_shelf_topics', function (Blueprint $table): void {
            $table->foreignUlid('shelf_id')->constrained('catalog_shelves')->cascadeOnDelete();
            $table->foreignUlid('topic_id')->constrained('catalog_topics')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(1);
            $table->primary(['shelf_id', 'topic_id']);
        });

        $rows = DB::table('catalog_shelves')
            ->join('catalog_signature_topics', 'catalog_signature_topics.signature_id', '=', 'catalog_shelves.signature_id')
            ->select('catalog_shelves.id as shelf_id', 'catalog_signature_topics.topic_id', 'catalog_signature_topics.position')
            ->get();

        foreach ($rows as $row) {
            DB::table('catalog_shelf_topics')->insertOrIgnore(['shelf_id' => $row->shelf_id, 'topic_id' => $row->topic_id, 'position' => $row->position]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_shelf_topics');
    }
};
