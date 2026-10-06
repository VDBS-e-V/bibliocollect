<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Anonymisierte Ausleihen und Vormerkungen verlieren ihre Verknüpfung zum Ausleihkonto (patron_id = null).
 * Der Datensatz bleibt für Statistiken erhalten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('circulation_loans', function (Blueprint $table): void {
            $table->foreignUlid('patron_id')->nullable()->change();
        });

        Schema::table('circulation_reservations', function (Blueprint $table): void {
            $table->foreignUlid('patron_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Anonymisierte Datensätze lassen sich nicht zurückverknüpfen; sie werden entfernt.
        DB::table('circulation_reservations')->whereNull('patron_id')->delete();
        DB::table('circulation_loans')->whereNull('patron_id')->delete();

        Schema::table('circulation_reservations', function (Blueprint $table): void {
            $table->foreignUlid('patron_id')->nullable(false)->change();
        });

        Schema::table('circulation_loans', function (Blueprint $table): void {
            $table->foreignUlid('patron_id')->nullable(false)->change();
        });
    }
};
