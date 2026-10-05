<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\CatalogImportRowStatus;
use App\Modules\Catalog\Enums\CatalogImportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_import_batches', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_format', 40);
            $table->string('original_filename', 255)->nullable();
            $table->string('status', 30)->default(CatalogImportStatus::Uploaded->value)->index();
            $table->json('headers');
            $table->json('mapping')->nullable();
            $table->json('summary')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('catalog_import_rows', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('batch_id')->constrained('catalog_import_batches')->restrictOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('status', 30)->default(CatalogImportRowStatus::Pending->value)->index();
            $table->json('raw_data');
            $table->json('source_errors');
            $table->json('normalized_data')->nullable();
            $table->json('plan')->nullable();
            $table->json('warnings');
            $table->json('conflicts');
            $table->timestamps();

            $table->unique(['batch_id', 'row_number'], 'catalog_import_row_number_unique');
            $table->index(['batch_id', 'status'], 'catalog_import_batch_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_import_rows');
        Schema::dropIfExists('catalog_import_batches');
    }
};
