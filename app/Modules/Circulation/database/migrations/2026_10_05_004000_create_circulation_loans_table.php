<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circulation_loans', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('patron_id')->constrained('patrons')->restrictOnDelete();
            $table->foreignUlid('copy_id')->constrained('catalog_copies')->restrictOnDelete();
            $table->dateTime('checked_out_at')->index();
            $table->date('due_on')->index();
            $table->timestamp('returned_at')->nullable()->index();
            $table->foreignId('checked_out_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patron_id', 'returned_at']);
            $table->index(['copy_id', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circulation_loans');
    }
};
