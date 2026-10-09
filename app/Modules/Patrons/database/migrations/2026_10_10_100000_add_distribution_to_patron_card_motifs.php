<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verteilung beim Drucken je Motiv: normal, mehr (doppelt so häufig) oder auslassen (kommt nicht vor, entspricht „ausgeschaltet“).
        Schema::table('patron_card_motifs', function (Blueprint $table): void {
            $table->string('distribution', 10)->default('normal');
        });

        DB::table('patron_card_motifs')->where('is_active', false)->update(['distribution' => 'skip']);
    }

    public function down(): void
    {
        Schema::table('patron_card_motifs', function (Blueprint $table): void {
            $table->dropColumn('distribution');
        });
    }
};
