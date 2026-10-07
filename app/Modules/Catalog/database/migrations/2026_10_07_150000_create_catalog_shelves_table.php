<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** Regalbretter: Der Standort eines Exemplars wird aus dieser Liste gewählt, nicht mehr frei getippt. */
    public function up(): void
    {
        Schema::create('catalog_shelves', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            // Bezeichnung des Regalbretts, wird als Standort am Exemplar gespeichert (zum Beispiel "R3-B2").
            $table->string('code', 40)->unique();
            // Was auf dem Regalbrett steht (zum Beispiel "Fantasy ab 10 Jahren").
            $table->string('label', 120)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Bisherige Freitext-Standorte werden zu Regalbrettern, damit nichts verloren geht.
        $now = now();
        $order = 0;

        foreach (DB::table('catalog_copies')->whereNotNull('shelf_location')->where('shelf_location', '!=', '')->distinct()->orderBy('shelf_location')->pluck('shelf_location') as $location) {
            DB::table('catalog_shelves')->insert([
                'id' => (string) Str::ulid(),
                'code' => mb_substr((string) $location, 0, 40),
                'label' => null,
                'sort_order' => ++$order,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_shelves');
    }
};
