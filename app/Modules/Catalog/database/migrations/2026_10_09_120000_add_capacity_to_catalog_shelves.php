<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Wie viele Bücher auf ein Regalbrett passen (freiwillig): Daraus ergibt sich, wo noch Platz ist. */
    public function up(): void
    {
        Schema::table('catalog_shelves', function (Blueprint $table): void {
            $table->unsignedSmallInteger('capacity')->nullable()->after('board');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_shelves', function (Blueprint $table): void {
            $table->dropColumn('capacity');
        });
    }
};
