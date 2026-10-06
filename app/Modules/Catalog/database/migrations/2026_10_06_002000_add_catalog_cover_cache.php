<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_editions', function (Blueprint $table): void {
            $table->string('cover_path', 500)->nullable();
            $table->string('cover_source', 80)->nullable()->index();
            $table->string('cover_source_reference', 1000)->nullable();
            $table->string('cover_status', 30)->nullable()->index();
            $table->timestamp('cover_checked_at')->nullable();
            $table->timestamp('cover_fetched_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_editions', function (Blueprint $table): void {
            $table->dropIndex(['cover_source']);
            $table->dropIndex(['cover_status']);
            $table->dropColumn([
                'cover_path',
                'cover_source',
                'cover_source_reference',
                'cover_status',
                'cover_checked_at',
                'cover_fetched_at',
            ]);
        });
    }
};
