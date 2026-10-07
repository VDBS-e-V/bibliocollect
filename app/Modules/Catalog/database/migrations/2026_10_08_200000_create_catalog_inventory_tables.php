<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Inventur: Bücher werden am Regalbrett gescannt und mit dem Bestand im System abgeglichen. */
    public function up(): void
    {
        Schema::create('catalog_inventory_counts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 120);
            $table->string('status', 10)->index();
            $table->foreignId('started_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('catalog_inventory_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('inventory_count_id')->constrained('catalog_inventory_counts')->cascadeOnDelete();
            $table->foreignUlid('copy_id')->nullable()->constrained('catalog_copies')->nullOnDelete();
            $table->string('barcode', 80);
            $table->string('shelf_code', 40);
            $table->timestamp('scanned_at');

            $table->unique(['inventory_count_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_inventory_items');
        Schema::dropIfExists('catalog_inventory_counts');
    }
};
