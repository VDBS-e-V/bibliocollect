<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_topics', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('legacy_source', 40)->nullable();
            $table->string('legacy_id', 80)->nullable();
            $table->string('public_key', 80)->nullable()->index();
            $table->foreignUlid('parent_id')->nullable()->constrained('catalog_topics')->nullOnDelete();
            $table->string('name', 255)->index();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['legacy_source', 'legacy_id'], 'catalog_topics_legacy_unique');
        });

        Schema::create('catalog_signatures', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('legacy_source', 40)->nullable();
            $table->string('legacy_id', 80)->nullable();
            $table->string('signature', 120)->unique();
            $table->timestamps();

            $table->unique(['legacy_source', 'legacy_id'], 'catalog_signatures_legacy_unique');
        });

        Schema::create('catalog_signature_topics', function (Blueprint $table): void {
            $table->foreignUlid('signature_id')->constrained('catalog_signatures')->cascadeOnDelete();
            $table->foreignUlid('topic_id')->constrained('catalog_topics')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);

            $table->primary(['signature_id', 'topic_id']);
            $table->index(['signature_id', 'position'], 'catalog_signature_topic_order_idx');
        });

        Schema::table('catalog_contributors', function (Blueprint $table): void {
            $table->string('gnd_id', 80)->nullable()->unique();
        });

        Schema::table('catalog_editions', function (Blueprint $table): void {
            $table->text('responsibility_statement')->nullable();
            $table->string('series_statement', 500)->nullable()->index();
            $table->string('publication_place', 255)->nullable();
            $table->string('edition_number', 120)->nullable();
            $table->json('alternate_identifiers')->nullable();
            $table->string('issn', 32)->nullable()->index();
            $table->string('doi_handle', 255)->nullable();
            $table->string('local_classification', 120)->nullable()->index();
            $table->string('original_language_code', 16)->nullable()->index();
            $table->unsignedInteger('page_count')->nullable();
            $table->string('physical_extent', 255)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('format_type', 80)->nullable();
            $table->text('summary')->nullable();
            $table->text('subject_keywords')->nullable();
            $table->text('subject_keywords_system')->nullable();
            $table->string('target_audience', 255)->nullable();
            $table->json('age_recommendation')->nullable();
            $table->string('metadata_source', 40)->nullable()->index();
            $table->string('source_record_id', 80)->nullable()->index();
            $table->string('source_permalink', 500)->nullable();
            $table->string('legacy_source', 40)->nullable();
            $table->char('legacy_record_key', 64)->nullable();

            $table->unique(
                ['legacy_source', 'legacy_record_key'],
                'catalog_editions_legacy_record_unique',
            );
        });

        Schema::table('catalog_copies', function (Blueprint $table): void {
            $table->foreignUlid('signature_id')->nullable()->constrained('catalog_signatures')->nullOnDelete();
            $table->string('legacy_source', 40)->nullable();
            $table->string('legacy_media_id', 80)->nullable();
            $table->boolean('legacy_in_transition')->nullable();
            $table->string('legacy_school_id', 32)->nullable();
            $table->string('access_status', 40)->nullable()->index();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->boolean('legacy_is_available')->nullable();
            $table->date('cataloged_on')->nullable();
            $table->string('legacy_cover_path', 1000)->nullable();
            $table->unsignedInteger('legacy_loan_count')->nullable();
            $table->date('legacy_last_loan_date')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('condition_code', 120)->nullable()->index();
            $table->string('legacy_condition', 120)->nullable();
            $table->string('depreciation_reason', 160)->nullable();
            $table->date('depreciated_at')->nullable();
            $table->string('further_use', 120)->nullable();
            $table->json('legacy_metadata')->nullable();

            $table->unique(
                ['legacy_source', 'legacy_media_id'],
                'catalog_copies_legacy_media_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('catalog_copies', function (Blueprint $table): void {
            $table->dropForeign(['signature_id']);
            $table->dropUnique('catalog_copies_legacy_media_unique');
            $table->dropIndex(['access_status']);
            $table->dropIndex(['condition_code']);
            $table->dropColumn([
                'signature_id',
                'legacy_source',
                'legacy_media_id',
                'legacy_in_transition',
                'legacy_school_id',
                'access_status',
                'purchase_date',
                'purchase_price',
                'legacy_is_available',
                'cataloged_on',
                'legacy_cover_path',
                'legacy_loan_count',
                'legacy_last_loan_date',
                'internal_notes',
                'condition_code',
                'legacy_condition',
                'depreciation_reason',
                'depreciated_at',
                'further_use',
                'legacy_metadata',
            ]);
        });

        Schema::table('catalog_editions', function (Blueprint $table): void {
            $table->dropUnique('catalog_editions_legacy_record_unique');
            $table->dropIndex(['series_statement']);
            $table->dropIndex(['issn']);
            $table->dropIndex(['local_classification']);
            $table->dropIndex(['original_language_code']);
            $table->dropIndex(['metadata_source']);
            $table->dropIndex(['source_record_id']);
            $table->dropColumn([
                'responsibility_statement',
                'series_statement',
                'publication_place',
                'edition_number',
                'alternate_identifiers',
                'issn',
                'doi_handle',
                'local_classification',
                'original_language_code',
                'page_count',
                'physical_extent',
                'file_size_bytes',
                'format_type',
                'summary',
                'subject_keywords',
                'subject_keywords_system',
                'target_audience',
                'age_recommendation',
                'metadata_source',
                'source_record_id',
                'source_permalink',
                'legacy_source',
                'legacy_record_key',
            ]);
        });

        Schema::table('catalog_contributors', function (Blueprint $table): void {
            $table->dropUnique(['gnd_id']);
            $table->dropColumn('gnd_id');
        });

        Schema::dropIfExists('catalog_signature_topics');
        Schema::dropIfExists('catalog_signatures');
        Schema::dropIfExists('catalog_topics');
    }
};
