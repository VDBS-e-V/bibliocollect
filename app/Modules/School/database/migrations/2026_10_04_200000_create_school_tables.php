<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_years', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 80)->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('school_classes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->string('name', 80);
            $table->unsignedTinyInteger('grade_level');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['school_year_id', 'name']);
        });

        Schema::create('library_opening_hours', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->unsignedTinyInteger('day_of_week')->unique();
            $table->boolean('is_open')->default(false);
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->timestamps();
        });

        Schema::create('library_closures', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->date('date')->unique();
            $table->string('reason', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_closures');
        Schema::dropIfExists('library_opening_hours');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('school_years');
    }
};
