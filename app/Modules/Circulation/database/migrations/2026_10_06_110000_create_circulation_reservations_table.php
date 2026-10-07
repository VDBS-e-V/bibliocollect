<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circulation_reservations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('patron_id')->constrained('patrons')->restrictOnDelete();
            $table->foreignUlid('title_id')->constrained('catalog_titles')->restrictOnDelete();
            $table->string('status', 20)->index();
            $table->dateTime('requested_at');
            // Gesetzt, sobald ein Exemplar zurückgelegt wurde (Status `ready`).
            $table->foreignUlid('ready_copy_id')->nullable()->constrained('catalog_copies')->nullOnDelete();
            $table->timestamp('ready_at')->nullable();
            $table->date('pickup_until')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->foreignUlid('loan_id')->nullable()->constrained('circulation_loans')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['title_id', 'status', 'requested_at']);
            $table->index(['patron_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circulation_reservations');
    }
};
