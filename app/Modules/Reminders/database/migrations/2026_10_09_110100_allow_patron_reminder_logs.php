<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Erinnerungen können auch an die Adresse eines Ausleihkontos ohne Onlinekonto gehen: Dann steht die Person statt des Kontos im Protokoll. */
    public function up(): void
    {
        Schema::table('reminder_logs', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('patron_id', 26)->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('reminder_logs', function (Blueprint $table): void {
            $table->dropIndex(['patron_id']);
            $table->dropColumn('patron_id');
        });
    }
};
