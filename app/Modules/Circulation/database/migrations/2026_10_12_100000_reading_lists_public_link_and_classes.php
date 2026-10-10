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
        // Öffentlicher Link: jede Liste bekommt ein nicht erratbares Kennzeichen.
        Schema::table('circulation_reading_lists', function (Blueprint $table): void {
            $table->string('public_token', 40)->nullable()->unique();
        });

        DB::table('circulation_reading_lists')->orderBy('id')->select('id')->get()->each(static function ($row): void {
            DB::table('circulation_reading_lists')->where('id', $row->id)->update(['public_token' => Str::random(32)]);
        });

        // Mehrere Klassen je Liste.
        Schema::create('circulation_reading_list_classes', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('reading_list_id')->constrained('circulation_reading_lists')->cascadeOnDelete();
            $table->foreignUlid('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->unique(['reading_list_id', 'school_class_id'], 'reading_list_class_unique');
        });

        DB::table('circulation_reading_lists')->whereNotNull('school_class_id')->select(['id', 'school_class_id'])->get()->each(static function ($row): void {
            DB::table('circulation_reading_list_classes')->insert(['reading_list_id' => $row->id, 'school_class_id' => $row->school_class_id]);
        });

        Schema::table('circulation_reading_lists', function (Blueprint $table): void {
            $table->dropForeign(['school_class_id']);
            $table->dropIndex(['school_class_id', 'is_published']);
        });

        Schema::table('circulation_reading_lists', function (Blueprint $table): void {
            $table->dropColumn('school_class_id');
        });
    }

    public function down(): void
    {
        Schema::table('circulation_reading_lists', function (Blueprint $table): void {
            $table->foreignUlid('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
        });

        DB::table('circulation_reading_list_classes')->orderBy('id')->get()->each(static function ($row): void {
            DB::table('circulation_reading_lists')->where('id', $row->reading_list_id)->whereNull('school_class_id')->update(['school_class_id' => $row->school_class_id]);
        });

        Schema::dropIfExists('circulation_reading_list_classes');

        Schema::table('circulation_reading_lists', function (Blueprint $table): void {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
