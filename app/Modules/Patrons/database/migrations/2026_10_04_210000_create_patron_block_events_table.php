<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patron_block_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('patron_id')->constrained('patrons')->restrictOnDelete();
            $table->string('action', 20)->index();
            $table->string('reason', 500)->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patron_block_events');
    }
};
