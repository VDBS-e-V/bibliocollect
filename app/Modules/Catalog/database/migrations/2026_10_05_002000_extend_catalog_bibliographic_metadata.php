<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_contributors', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('display_name', 255);
            $table->string('sort_name', 255)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('catalog_title_contributions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('title_id')->constrained('catalog_titles')->cascadeOnDelete();
            $table->foreignUlid('contributor_id')->constrained('catalog_contributors')->cascadeOnDelete();
            $table->string('role_key', 80);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(
                ['title_id', 'contributor_id', 'role_key'],
                'catalog_contribution_unique',
            );
            $table->index(
                ['title_id', 'position'],
                'catalog_contribution_order_idx',
            );
        });

        Schema::table('catalog_editions', function (Blueprint $table): void {
            $table->string('media_type', 80)->nullable()->index();
            $table->string('language_code', 16)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_editions', function (Blueprint $table): void {
            $table->dropIndex(['media_type']);
            $table->dropIndex(['language_code']);
            $table->dropColumn(['media_type', 'language_code']);
        });

        Schema::dropIfExists('catalog_title_contributions');
        Schema::dropIfExists('catalog_contributors');
    }
};
