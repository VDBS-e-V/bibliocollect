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
        // Ein Motiv gehört zu Vorder- und Rückseite zugleich. Pfade sind relativ zu public/.
        Schema::create('patron_card_motifs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 80);
            $table->string('front_path', 200)->nullable();
            $table->string('back_path', 200)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('patron_cards', function (Blueprint $table): void {
            $table->foreignUlid('motif_id')->nullable()->constrained('patron_card_motifs')->nullOnDelete();
        });

        // Bestehende Einzelmotive über den Namen paaren. Ohne Partner bleibt eine Seite leer; das Motiv ist dann ausgeschaltet,
        // bis die fehlende Seite ergänzt wurde (Dateien gehen nicht verloren).
        $designs = DB::table('patron_card_designs')->orderBy('sort_order')->orderBy('name')->get();
        $back = $designs->where('side', 'back')->keyBy('name');
        $used = [];
        $now = now();
        $order = 0;

        foreach ($designs->where('side', 'front') as $front) {
            $partner = $back->get($front->name);
            $used[] = $partner?->id;

            DB::table('patron_card_motifs')->insert([
                'id' => (string) Str::ulid(),
                'name' => $front->name,
                'front_path' => $front->path,
                'back_path' => $partner?->path,
                'is_active' => $partner !== null && $front->is_active && $partner->is_active,
                'sort_order' => ++$order,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($back->reject(static fn (object $design): bool => in_array($design->id, $used, true)) as $design) {
            DB::table('patron_card_motifs')->insert([
                'id' => (string) Str::ulid(),
                'name' => $design->name,
                'front_path' => null,
                'back_path' => $design->path,
                'is_active' => false,
                'sort_order' => ++$order,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::dropIfExists('patron_card_designs');
    }

    public function down(): void
    {
        Schema::table('patron_cards', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('motif_id');
        });

        Schema::dropIfExists('patron_card_motifs');
    }
};
