<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Neu erfasste Exemplare liegen auf einem Stapel, bis sie ins Regal einsortiert und dort vermerkt werden. */
    public function up(): void
    {
        Schema::table('catalog_copies', function (Blueprint $table): void {
            $table->boolean('needs_shelving')->default(false)->index();
            $table->timestamp('shelved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_copies', function (Blueprint $table): void {
            $table->dropColumn(['needs_shelving', 'shelved_at']);
        });
    }
};
