<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Unerwartete Fehler im Betrieb, gleiche Fehler zusammengefasst. Wird nach 30 Tagen aufgeräumt. */
    public function up(): void
    {
        Schema::create('system_error_events', function (Blueprint $table): void {
            $table->id();
            $table->string('fingerprint', 64)->index();
            $table->string('kind', 20)->default('exception');
            $table->string('class', 190);
            $table->string('message', 300);
            $table->string('file', 190)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('path', 190)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('occurrences')->default(1);
            $table->dateTime('first_seen_at');
            $table->dateTime('last_seen_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_error_events');
    }
};
