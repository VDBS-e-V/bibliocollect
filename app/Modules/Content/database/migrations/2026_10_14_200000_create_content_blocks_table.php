<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Textbausteine: kurze, bearbeitbare Hinweise an festen Stellen (Startseite, Katalog), optional befristet.
        Schema::create('content_blocks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key', 60)->unique();
            $table->text('body');
            $table->boolean('is_active')->default(false);
            $table->date('visible_from')->nullable();
            $table->date('visible_until')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_blocks');
    }
};
