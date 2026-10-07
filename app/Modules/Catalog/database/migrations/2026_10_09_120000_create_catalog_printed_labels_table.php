<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Inventarnummern, deren Etiketten auf Vorrat gedruckt wurden (noch nicht unbedingt einem Exemplar zugeordnet). */
    public function up(): void
    {
        Schema::create('catalog_printed_labels', function (Blueprint $table): void {
            $table->string('number', 7)->primary();
            $table->dateTime('printed_at');
            $table->unsignedBigInteger('printed_by_user_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_printed_labels');
    }
};
