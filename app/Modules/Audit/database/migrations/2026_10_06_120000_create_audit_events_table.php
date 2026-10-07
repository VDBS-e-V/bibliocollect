<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->dateTime('occurred_at')->index();
            // Ohne Fremdschlüssel: Das Protokoll soll das Löschen von Konten überdauern.
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('action', 80)->index();
            $table->string('subject_type', 80)->nullable();
            $table->string('subject_id', 40)->nullable();
            $table->string('summary', 500);
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
