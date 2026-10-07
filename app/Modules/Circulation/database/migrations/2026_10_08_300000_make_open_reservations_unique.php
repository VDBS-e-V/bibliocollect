<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jedes Ausleihkonto darf einen Titel nur einmal offen vorgemerkt haben. Die Anwendung prüft das schon; der eindeutige
 * Schlüssel `open_key` (Konto und Titel, nur solange die Vormerkung offen ist) macht es auch bei gleichzeitigen Anfragen
 * unmöglich. Bereits vorhandene Doppelte werden bereinigt: Die älteste bleibt, die späteren gelten als storniert.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('circulation_reservations', function (Blueprint $table): void {
            $table->string('open_key', 64)->nullable()->after('status');
        });

        $seen = [];

        $open = DB::table('circulation_reservations')
            ->whereIn('status', ['waiting', 'ready'])
            ->whereNotNull('patron_id')
            ->orderBy('requested_at')
            ->orderBy('id')
            ->get(['id', 'patron_id', 'title_id']);

        foreach ($open as $row) {
            $key = $row->patron_id.'|'.$row->title_id;

            if (isset($seen[$key])) {
                DB::table('circulation_reservations')->where('id', $row->id)->update([
                    'status' => 'cancelled',
                    'ready_copy_id' => null,
                    'pickup_until' => null,
                    'closed_at' => now(),
                ]);

                continue;
            }

            $seen[$key] = true;
            DB::table('circulation_reservations')->where('id', $row->id)->update(['open_key' => $key]);
        }

        Schema::table('circulation_reservations', function (Blueprint $table): void {
            $table->unique('open_key', 'circulation_reservations_open_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('circulation_reservations', function (Blueprint $table): void {
            $table->dropUnique('circulation_reservations_open_key_unique');
            $table->dropColumn('open_key');
        });
    }
};
