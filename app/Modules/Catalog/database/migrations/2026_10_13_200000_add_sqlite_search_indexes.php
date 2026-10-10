<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite legt für Fremdschlüssel keinen Index an (MariaDB/MySQL tun das). Die Katalogsuche fragt je Titel nach Ausgaben und Exemplaren;
 * ohne Index dauert das lokal und in Tests mit tausend Titeln Sekunden. Auf dem Webspace (MariaDB) ändert diese Migration nichts.
 */
return new class extends Migration
{
    /** @var array<string, array<string, string>> Tabelle => [Indexname => Spalte] */
    private const INDEXES = [
        'catalog_copies' => ['catalog_copies_edition_id_idx' => 'edition_id'],
        'catalog_editions' => ['catalog_editions_title_id_idx' => 'title_id'],
        'catalog_shelf_topics' => ['catalog_shelf_topics_shelf_id_idx' => 'shelf_id', 'catalog_shelf_topics_topic_id_idx' => 'topic_id'],
        'catalog_title_contributions' => ['catalog_title_contributions_contributor_idx' => 'contributor_id'],
    ];

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $name => $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                    Schema::table($table, static function (Blueprint $blueprint) use ($column, $name): void {
                        $blueprint->index($column, $name);
                    });
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        foreach (self::INDEXES as $table => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if (Schema::hasTable($table)) {
                    Schema::table($table, static function (Blueprint $blueprint) use ($name): void {
                        $blueprint->dropIndex($name);
                    });
                }
            }
        }
    }
};
