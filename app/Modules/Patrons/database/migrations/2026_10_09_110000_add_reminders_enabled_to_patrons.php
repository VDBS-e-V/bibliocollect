<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Erinnerungen per E-Mail an die Adresse am Ausleihkonto (auch ohne Onlinekonto); das Personal kann sie je Konto ausschalten. */
    public function up(): void
    {
        Schema::table('patrons', function (Blueprint $table): void {
            $table->boolean('reminders_enabled')->default(true)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('patrons', function (Blueprint $table): void {
            $table->dropColumn('reminders_enabled');
        });
    }
};
