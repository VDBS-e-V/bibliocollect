<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Druckaufträge für Etiketten auf Vorrat. Die gedruckten Nummern verweisen auf ihren Auftrag, damit sich ein Auftrag einzeln zurücknehmen lässt. */
    public function up(): void
    {
        Schema::create('catalog_label_runs', function (Blueprint $table): void {
            $table->id();
            $table->dateTime('printed_at');
            $table->unsignedBigInteger('printed_by_user_id')->nullable();
            $table->string('mode', 20);
            $table->unsignedInteger('label_count');
            $table->string('first_number', 7);
            $table->string('last_number', 7);
        });

        Schema::table('catalog_printed_labels', function (Blueprint $table): void {
            $table->unsignedBigInteger('run_id')->nullable()->index()->after('number');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_printed_labels', function (Blueprint $table): void {
            $table->dropIndex(['run_id']);
            $table->dropColumn('run_id');
        });

        Schema::dropIfExists('catalog_label_runs');
    }
};
