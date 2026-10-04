<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_titles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('preferred_title', 500);
            $table->string('subtitle', 500)->nullable();
            $table->string('sort_title', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('catalog_editions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('title_id')->constrained('catalog_titles')->restrictOnDelete();
            $table->string('edition_statement', 255)->nullable();
            $table->string('isbn', 32)->nullable()->index();
            $table->string('publisher_name', 255)->nullable();
            $table->unsignedSmallInteger('publication_year')->nullable()->index();
            $table->unsignedTinyInteger('minimum_age')->nullable()->index();
            $table->string('age_rating_label', 80)->nullable();
            $table->timestamps();
        });

        Schema::create('catalog_copies', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('edition_id')->constrained('catalog_editions')->restrictOnDelete();
            $table->string('barcode', 80)->unique();
            $table->string('status', 30)->default('active')->index();
            $table->string('shelf_location', 120)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_copies');
        Schema::dropIfExists('catalog_editions');
        Schema::dropIfExists('catalog_titles');
    }
};
