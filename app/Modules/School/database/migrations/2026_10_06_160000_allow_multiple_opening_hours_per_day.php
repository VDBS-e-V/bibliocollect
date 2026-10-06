<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Ein Wochentag kann mehrere Öffnungszeiträume haben, z. B. 08:00–10:00 und 13:00–15:00. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_opening_hours', function (Blueprint $table): void {
            $table->dropUnique(['day_of_week']);
            $table->index('day_of_week');
        });
    }

    public function down(): void
    {
        // Je Tag bleibt nur der erste Zeitraum erhalten.
        $keep = DB::table('library_opening_hours')
            ->orderBy('opens_at')
            ->get()
            ->unique('day_of_week')
            ->pluck('id')
            ->all();

        DB::table('library_opening_hours')->whereNotIn('id', $keep)->delete();

        Schema::table('library_opening_hours', function (Blueprint $table): void {
            $table->dropIndex(['day_of_week']);
            $table->unique('day_of_week');
        });
    }
};
