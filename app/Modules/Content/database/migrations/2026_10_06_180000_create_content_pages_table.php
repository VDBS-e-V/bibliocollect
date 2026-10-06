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
        Schema::create('content_pages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('slug', 60)->unique();
            $table->string('title', 160);
            $table->longText('body');
            // Seiten mit Platzhaltertext zeigen einen Hinweis, bis jemand sie bearbeitet hat.
            $table->boolean('is_placeholder')->default(false);
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();

        foreach ([
            ['impressum', 'Impressum', "## Angaben nach § 5 DDG\n\nDiese Seite muss vom Betreiber der Bibliothek ausgefüllt werden: Name und Anschrift des Vereins oder der Schule, Vertretungsberechtigte, Kontakt (E-Mail, Telefon), Registereintrag und Verantwortliche für den Inhalt."],
            ['datenschutz', 'Datenschutzerklärung', "## Verantwortliche Stelle\n\nName und Kontaktdaten des Verantwortlichen sowie der oder des Datenschutzbeauftragten müssen hier eingetragen werden.\n\n## Welche Daten wir speichern\n\nAusleihkonto (Name, Geburtsdatum, Klasse, Bibliotheksnummer, optional E-Mail), Onlinekonto, Ausleihen und Vormerkungen, Erinnerungs-E-Mails.\n\n## Wie lange wir Daten speichern\n\nAusleihen, Vormerkungen und Ereignisse werden drei Jahre nach Abschluss anonymisiert. Ausgeschiedene Ausleihkonten werden drei Jahre nach dem Austritt anonymisiert.\n\n## Deine Rechte\n\nDu kannst Auskunft über deine Daten verlangen (im Portal unter „Meine gespeicherten Daten herunterladen“), sie berichtigen oder löschen lassen und der Verarbeitung widersprechen. Erinnerungs-E-Mails lassen sich im Portal abschalten.\n\nDieser Text ist ein Entwurf und muss vom Verantwortlichen geprüft und ergänzt werden."],
            ['barrierefreiheit', 'Erklärung zur Barrierefreiheit', "## Stand der Barrierefreiheit\n\nDiese Erklärung muss nach einer Prüfung der Seiten ergänzt werden: Grad der Vereinbarkeit mit den Anforderungen, nicht barrierefreie Bereiche, Datum der Erstellung und Kontakt für Rückmeldungen."],
        ] as [$slug, $title, $body]) {
            DB::table('content_pages')->insert([
                'id' => Str::lower((string) Str::ulid()),
                'slug' => $slug,
                'title' => $title,
                'body' => $body,
                'is_placeholder' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_pages');
    }
};
