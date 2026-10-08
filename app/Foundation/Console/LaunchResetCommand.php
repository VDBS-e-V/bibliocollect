<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Entfernt alle Demo- und Testdaten vor dem Start im echten Betrieb: Personen, Ausleihen, Vormerkungen, Wünsche, Konten, Schuljahre und
 * Klassen, Protokoll, Etikettenläufe, Inventuren sowie Titel ohne Herkunft aus dem Altsystem. Bleiben: der Altbestand samt Exemplaren,
 * Signaturen, Regalbretter, Themenbereiche, Regeln (Einstellungen), Seitentexte, Öffnungszeiten, Schließtage und Ausweis-Designs.
 * Vorher wird eine Sicherung erstellt. Danach gibt es kein Konto mehr: Das erste Verwaltungskonto entsteht auf /_setup.
 */
final class LaunchResetCommand extends Command
{
    protected $signature = 'app:launch-reset {--force : Ohne Rückfrage ausführen} {--skip-backup : Ohne vorherige Sicherung (nur für Tests)}';

    protected $description = 'Entfernt alle Demo- und Testdaten (Personen, Ausleihen, Konten, Klassen, Protokoll) und behält den Altbestand des Katalogs.';

    /** Tabellen, die vollständig geleert werden (Reihenfolge egal, Fremdschlüssel sind währenddessen aus). */
    private const WIPE = [
        'circulation_transactions', 'circulation_loans', 'circulation_reservations', 'circulation_book_wishes',
        'reminder_logs',
        'patron_cards', 'patron_status_events', 'patron_block_events', 'patron_account_link_tokens', 'patrons',
        'user_role_assignments', 'password_reset_tokens', 'users',
        'school_classes', 'school_years',
        'audit_events', 'system_error_events',
        'catalog_inventory_items', 'catalog_inventory_counts', 'catalog_label_runs', 'catalog_printed_labels',
        'catalog_import_rows', 'catalog_import_batches',
        'sessions', 'cache', 'cache_locks', 'jobs', 'failed_jobs',
    ];

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Wirklich ALLE Demo- und Testdaten löschen? Der Altbestand des Katalogs bleibt. Vorher wird eine Sicherung erstellt.')) {
            $this->warn('Abgebrochen. Es wurde nichts verändert.');

            return self::FAILURE;
        }

        if (! $this->option('skip-backup')) {
            $this->call('backup:database');
        }

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function (): void {
                foreach (self::WIPE as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }

                $this->removeNonLegacyCatalog();
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('Fertig. Alle Demo- und Testdaten sind entfernt.');
        $this->line('Nächste Schritte: Verwaltungskonto auf /_setup anlegen, Schuljahr und Klassen unter Verwaltung → Schule anlegen, Bestand und Personen einspielen.');

        return self::SUCCESS;
    }

    /** Titel, Ausgaben und Exemplare ohne Altsystem-Herkunft (Demo- und Testtitel), samt verwaister Autor:innen. */
    private function removeNonLegacyCatalog(): void
    {
        $editionIds = DB::table('catalog_editions')->whereNull('legacy_source')->pluck('id')->all();

        if ($editionIds === []) {
            return;
        }

        DB::table('catalog_copies')->whereIn('edition_id', $editionIds)->delete();
        DB::table('catalog_metadata_reviews')->whereIn('edition_id', $editionIds)->delete();
        DB::table('catalog_editions')->whereIn('id', $editionIds)->delete();

        $orphanTitles = DB::table('catalog_titles')->whereNotIn('id', DB::table('catalog_editions')->select('title_id'))->pluck('id')->all();
        DB::table('catalog_title_contributions')->whereIn('title_id', $orphanTitles)->delete();
        DB::table('catalog_titles')->whereIn('id', $orphanTitles)->delete();
        DB::table('catalog_contributors')->whereNotIn('id', DB::table('catalog_title_contributions')->select('contributor_id'))->delete();
    }
}
