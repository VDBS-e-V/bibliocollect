<?php

declare(strict_types=1);

use App\Modules\Catalog\Services\SeriesService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_series', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 190);
            $table->string('slug', 130)->unique();
            $table->string('key', 190)->unique();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
        });

        Schema::table('catalog_editions', function (Blueprint $table): void {
            $table->foreignUlid('series_id')->nullable()->constrained('catalog_series')->nullOnDelete();
            $table->string('series_volume', 30)->nullable();
        });

        // Bestehende Reihenangaben zuordnen.
        app(SeriesService::class)->syncAll();
    }

    public function down(): void
    {
        Schema::table('catalog_editions', function (Blueprint $table): void {
            $table->dropForeign(['series_id']);
            $table->dropColumn(['series_id', 'series_volume']);
        });

        Schema::dropIfExists('catalog_series');
    }
};
