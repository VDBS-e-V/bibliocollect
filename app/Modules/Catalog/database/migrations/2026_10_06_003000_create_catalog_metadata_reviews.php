<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_metadata_reviews', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('edition_id')->unique()->constrained('catalog_editions')->cascadeOnDelete();

            // open = braucht Prüfung, dismissed = bewusst ohne Handlungsbedarf, resolved = Probleme behoben.
            $table->string('status', 20)->default('open')->index();
            $table->json('issues');
            $table->unsignedSmallInteger('severity')->default(0)->index();

            // Hash der prüfrelevanten Felder. Ändert er sich, ist ein früherer Entscheid nicht mehr gültig.
            $table->string('fingerprint', 64);

            $table->json('proposal')->nullable();
            $table->string('proposal_source', 20)->nullable();
            $table->string('proposal_state', 20)->nullable();
            $table->timestamp('proposal_fetched_at')->nullable();

            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            // Fortlaufendes Protokoll der Entscheidungen (wer, wann, was geändert wurde).
            $table->json('history')->nullable();

            $table->timestamps();

            $table->index(['status', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_metadata_reviews');
    }
};
