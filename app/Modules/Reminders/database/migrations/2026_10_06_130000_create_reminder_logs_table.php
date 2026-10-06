<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('kind', 40);
            $table->string('subject_id', 40);
            // Unterscheidet wiederholte Erinnerungen zum selben Gegenstand (z. B. Überfälligkeitsstufe).
            $table->string('stage', 20)->default('');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['kind', 'subject_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};
