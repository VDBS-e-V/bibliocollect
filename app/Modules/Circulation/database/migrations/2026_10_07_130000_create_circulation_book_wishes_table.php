<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circulation_book_wishes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            // Leer bei Wünschen, die am Tresen ohne Person erfasst wurden, und nach der Anonymisierung.
            $table->foreignUlid('patron_id')->nullable()->constrained('patrons')->nullOnDelete();
            $table->string('title', 255);
            $table->string('author', 255)->nullable();
            $table->string('isbn', 20)->nullable()->index();
            $table->string('note', 500)->nullable();
            $table->string('status', 20)->index();
            $table->string('answer', 500)->nullable();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circulation_book_wishes');
    }
};
