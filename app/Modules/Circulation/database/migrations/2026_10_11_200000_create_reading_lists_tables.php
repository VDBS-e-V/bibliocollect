<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Leselisten von Lehrkräften: eine Auswahl an Titeln für eine Klasse.
        Schema::create('circulation_reading_lists', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->string('name', 120);
            $table->string('description', 500)->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->index(['school_class_id', 'is_published']);
        });

        Schema::create('circulation_reading_list_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('reading_list_id')->constrained('circulation_reading_lists')->cascadeOnDelete();
            $table->foreignUlid('title_id')->constrained('catalog_titles')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->unique(['reading_list_id', 'title_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circulation_reading_list_items');
        Schema::dropIfExists('circulation_reading_lists');
    }
};
