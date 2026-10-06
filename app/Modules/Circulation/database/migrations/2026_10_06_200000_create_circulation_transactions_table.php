<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ein am Tresen bestätigter Vorgang (mehrere Ausleihen, Verlängerungen, Rückgaben) mit Beleg.
        Schema::create('circulation_transactions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('number', 30)->unique();
            $table->foreignUlid('patron_id')->nullable()->constrained('patrons')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('items');
            $table->unsignedSmallInteger('checked_out_count')->default(0);
            $table->unsignedSmallInteger('renewed_count')->default(0);
            $table->unsignedSmallInteger('returned_count')->default(0);
            $table->string('emailed_to')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circulation_transactions');
    }
};
