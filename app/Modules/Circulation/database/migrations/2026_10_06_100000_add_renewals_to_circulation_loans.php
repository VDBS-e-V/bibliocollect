<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('circulation_loans', function (Blueprint $table): void {
            $table->unsignedSmallInteger('renewal_count')->default(0);
            $table->timestamp('last_renewed_at')->nullable();
            $table->foreignId('last_renewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('circulation_loans', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('last_renewed_by_user_id');
            $table->dropColumn(['renewal_count', 'last_renewed_at']);
        });
    }
};
