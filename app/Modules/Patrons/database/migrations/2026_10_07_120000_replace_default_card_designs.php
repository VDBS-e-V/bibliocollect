<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** Ersetzt die ersten Hintergrundmotive (motiv-N.png) durch die fertig gestalteten Vorder- und Rückseiten. */
    public function up(): void
    {
        $old = DB::table('patron_card_designs')->where('path', 'like', 'brand/vdbs/card-defaults/motiv-%');

        if (! $old->exists()) {
            return;
        }

        $old->delete();

        $defaults = [
            'front' => [1 => 'Quadrate', 2 => 'Bögen', 3 => 'Punkte (lila)', 4 => 'Punkte (grün)'],
            'back' => [1 => 'Punkte (lila)', 2 => 'Punkte (grün)', 3 => 'Quadrate', 4 => 'Bögen'],
        ];
        $now = now();

        foreach ($defaults as $side => $names) {
            foreach ($names as $number => $name) {
                DB::table('patron_card_designs')->insert([
                    'id' => (string) Str::ulid(),
                    'side' => $side,
                    'name' => $name,
                    'path' => 'brand/vdbs/card-defaults/'.$side.'-'.$number.'.png',
                    'is_active' => true,
                    'sort_order' => $number,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void {}
};
