<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('circulation_loans', function (Blueprint $table): void {
            // Wie die Ausleihe endete: returned (normal zurückgegeben), damaged (beschädigt zurückgegeben), lost (als verloren gemeldet).
            $table->string('outcome', 20)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('circulation_loans', function (Blueprint $table): void {
            $table->dropIndex(['outcome']);
            $table->dropColumn('outcome');
        });
    }
};
