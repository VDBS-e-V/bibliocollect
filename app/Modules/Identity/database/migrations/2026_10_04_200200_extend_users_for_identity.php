<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->unique()->after('id');
        });

        DB::table('users')
            ->whereNull('public_id')
            ->pluck('id')
            ->each(function (mixed $id): void {
                DB::table('users')
                    ->where('id', $id)
                    ->update(['public_id' => (string) Str::ulid()]);
            });

        Schema::create('user_role_assignments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role_key', 80);
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'role_key']);
            $table->index('role_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role_assignments');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
