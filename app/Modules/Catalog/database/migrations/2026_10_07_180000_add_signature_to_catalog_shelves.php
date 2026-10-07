<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Ein Regalbrett kann zu einer Signatur gehören und damit zu den Themenbereichen, die dort stehen. */
    public function up(): void
    {
        Schema::table('catalog_shelves', function (Blueprint $table): void {
            $table->foreignUlid('signature_id')->nullable()->after('label')->constrained('catalog_signatures')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_shelves', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('signature_id');
        });
    }
};
