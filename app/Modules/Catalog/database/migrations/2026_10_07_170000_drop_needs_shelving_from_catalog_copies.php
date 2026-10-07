<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Der Stapel „Einsortieren“ ergibt sich aus dem Standort: Wer keinen hat, ist nicht einsortiert. */
    public function up(): void
    {
        Schema::table('catalog_copies', function (Blueprint $table): void {
            $table->dropIndex(['needs_shelving']);
            $table->dropColumn('needs_shelving');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_copies', function (Blueprint $table): void {
            $table->boolean('needs_shelving')->default(false)->index();
        });
    }
};
