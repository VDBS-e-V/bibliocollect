<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Hintergrundmotive für Vorder- und Rückseiten der Ausweise. Pfade sind relativ zu public/.
        Schema::create('patron_card_designs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('side', 5)->index();
            $table->string('name', 80);
            $table->string('path', 200);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        // Mitgelieferte, fertig gestaltete Motive (siehe auch die Folgemigration für bestehende Installationen).
        $defaults = [
            'front' => [1 => 'Quadrate', 2 => 'Bögen', 3 => 'Punkte (lila)', 4 => 'Punkte (grün)'],
            'back' => [1 => 'Punkte (lila)', 2 => 'Punkte (grün)', 3 => 'Quadrate', 4 => 'Bögen'],
        ];

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

    public function down(): void
    {
        Schema::dropIfExists('patron_card_designs');
    }
};
