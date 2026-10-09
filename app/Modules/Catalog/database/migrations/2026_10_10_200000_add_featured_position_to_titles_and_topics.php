<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Von Hand empfohlene Titel und Themen für die Startseite. Leer = nicht empfohlen, die Zahl ist die Reihenfolge.
        Schema::table('catalog_titles', function (Blueprint $table): void {
            $table->unsignedSmallInteger('featured_position')->nullable()->index();
        });

        Schema::table('catalog_topics', function (Blueprint $table): void {
            $table->unsignedSmallInteger('featured_position')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_topics', function (Blueprint $table): void {
            $table->dropIndex(['featured_position']);
            $table->dropColumn('featured_position');
        });

        Schema::table('catalog_titles', function (Blueprint $table): void {
            $table->dropIndex(['featured_position']);
            $table->dropColumn('featured_position');
        });
    }
};
