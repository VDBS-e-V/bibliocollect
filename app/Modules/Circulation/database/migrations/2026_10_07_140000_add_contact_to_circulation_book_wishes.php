<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Öffentliche Wünsche ohne Ausleihkonto: freiwilliger Name und E-Mail-Adresse für die Rückmeldung. */
    public function up(): void
    {
        Schema::table('circulation_book_wishes', function (Blueprint $table): void {
            $table->string('contact_name', 120)->nullable()->after('note');
            $table->string('contact_email', 190)->nullable()->after('contact_name');
        });
    }

    public function down(): void
    {
        Schema::table('circulation_book_wishes', function (Blueprint $table): void {
            $table->dropColumn(['contact_name', 'contact_email']);
        });
    }
};
