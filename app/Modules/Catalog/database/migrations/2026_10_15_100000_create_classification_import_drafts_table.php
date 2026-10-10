<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Importentwurf: die geprüften Dateien (außerhalb des Webroots) mit Prüfsumme und Ablaufzeit. Bestätigt wird genau dieser Entwurf.
        Schema::create('catalog_classification_import_drafts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('topics_path', 255)->nullable();
            $table->string('signatures_path', 255)->nullable();
            $table->string('topics_sha256', 64)->nullable();
            $table->string('signatures_sha256', 64)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_classification_import_drafts');
    }
};
