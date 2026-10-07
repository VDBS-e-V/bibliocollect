<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ausweisnummern werden nie gelöscht und nie wiederverwendet; die Eindeutigkeit gilt für immer.
        Schema::create('patron_cards', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('number', 16)->unique();
            $table->unsignedInteger('batch')->nullable()->index();
            $table->string('status', 20)->index();
            $table->foreignUlid('patron_id')->nullable()->constrained('patrons')->nullOnDelete();
            $table->timestamp('printed_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->string('block_reason', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patron_cards');
    }
};
