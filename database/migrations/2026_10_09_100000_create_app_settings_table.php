<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Im Web geänderte Regeln (Leihfristen, Höchstzahlen, Vormerken, Erinnerungen). Ohne Zeile gilt der Wert aus der Konfigurationsdatei. */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table): void {
            $table->string('key', 120)->primary();
            $table->text('value');
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
