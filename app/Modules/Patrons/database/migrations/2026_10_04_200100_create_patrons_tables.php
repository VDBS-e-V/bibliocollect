<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrons', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('library_number', 80)->unique();
            $table->string('kind', 30)->index();
            $table->string('status', 30)->default('active')->index();
            $table->string('first_name', 120);
            $table->string('last_name', 120);
            $table->date('birth_date');
            $table->string('email')->nullable()->index();
            $table->foreignUlid('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->date('leaving_on')->nullable()->index();
            $table->timestamp('blocked_at')->nullable()->index();
            $table->string('blocked_reason', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('patron_account_link_tokens', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('patron_id')->constrained('patrons')->cascadeOnDelete();
            $table->char('fingerprint', 64)->unique();
            $table->dateTime('expires_at')->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patron_account_link_tokens');
        Schema::dropIfExists('patrons');
    }
};
