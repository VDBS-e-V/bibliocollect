<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Die Verteilung (normal, mehr, auslassen) gilt nur für einen Druckvorgang und wird nicht am Motiv gespeichert.
        if (Schema::hasColumn('patron_card_motifs', 'distribution')) {
            Schema::table('patron_card_motifs', function (Blueprint $table): void {
                $table->dropColumn('distribution');
            });
        }
    }

    public function down(): void
    {
        // Nichts zu tun: Die Spalte gehört nicht mehr zum Schema.
    }
};
