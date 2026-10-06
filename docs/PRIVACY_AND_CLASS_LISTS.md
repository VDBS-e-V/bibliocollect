# Datenschutz-Fristen und Klassenlisten (v0.6.1)

## Entscheidungen der Schule

- **Alles wird nach 3 Jahren anonymisiert** (`config/privacy.php`, `retention_years`).
- **Keine Mahnungen und Gebühren.** Es bleiben die Erinnerungs-E-Mails (`docs/PORTAL_AND_REMINDERS.md`) und Sammeldrucke für die Klassenleitungen.

## Anonymisierung

`php artisan privacy:anonymize` (Scheduler: sonntags 02:00 Uhr) mit `--dry-run` zum reinen Zählen. Anonymisiert heißt: Der Datensatz bleibt für Statistiken erhalten, verliert aber jeden Bezug zu Personen. Der Lauf ist wiederholbar und schreibt bei Änderungen ein Protokollereignis `privacy.anonymization.run` mit den Zahlen.

| Daten | Frist beginnt | Was passiert |
|---|---|---|
| Ausleihen (`circulation_loans`) | Rückgabe | Ausleihkonto und handelnde Konten entfallen (`patron_id` und `*_by_user_id` = null). Exemplar und Daten bleiben. Offene Ausleihen werden nie angefasst. |
| Vormerkungen | Abschluss (erfüllt, storniert, abgelaufen) | wie Ausleihen. Offene Vormerkungen bleiben. |
| Belege der Ausleihe (Vorgänge) | Erstellung | Person, handelndes Konto und die E-Mail-Adresse, an die der Beleg ging, entfallen; die Positionen bleiben. |
| Ausleihkonten ausgeschiedener Personen | Austrittsdatum | Name „Anonymisiert“, Bibliotheksnummer `ANON-<ID>`, nur das Geburtsjahr bleibt (1. Januar), E-Mail, Sperrgrund und Klasse entfallen. |
| Onlinekonten dieser Personen | wie das Ausleihkonto | Name „Anonymisiert“, Platzhalter-E-Mail, neues Zufallspasswort, Verknüpfung entfällt. |
| Sperr- und Statusereignisse der Ausleihkonten | Ereigniszeitpunkt | Grund und handelndes Konto entfallen. |
| Protokoll (`audit_events`) | Ereigniszeitpunkt | handelndes Konto und `patron_id` im Kontext entfallen; Aktion, Beschreibung und übrige Kennungen bleiben. |
| Erinnerungsprotokoll (`reminder_logs`) | Versandzeitpunkt | wird gelöscht. |

Nicht angefasst werden aktive Ausleihkonten, Katalogdaten und der Kalender. Ein Ausleihkonto ohne Austritt wird nie anonymisiert; Austritte laufen über den Schuljahreswechsel oder das einzelne Ausscheiden.

Technisch: `circulation_loans.patron_id` und `circulation_reservations.patron_id` sind seit der Migration `2026_10_06_140000_…` nullable. Anzeigen, die ein Ausleihkonto benötigen, betreffen nur offene Vorgänge und sind davon nicht berührt.

## Klassenlisten für die Klassenleitungen

`/betrieb/klassenlisten` (Navigation „Klassenlisten“, Recht `circulation.reports` für Mitarbeiter:innen und Verwaltung, nicht für die Schüler-AG) listet je Klasse des aktiven Schuljahres die Ausleihen:

- „Nur überfällige“ (Standard) oder „Alle offenen“, optional für eine einzelne Klasse.
- Je Klasse: Name (Nachname, Vorname), Medium, Barcode, Fälligkeit und Tage überfällig, sortiert nach Name.
- Lehrkräfte, Mitarbeiter:innen und Konten mit Klasse aus einem älteren Schuljahr stehen unter „Ohne Klasse“ am Ende.
- Der Knopf „Drucken“ öffnet den Druckdialog des Browsers. Beim Drucken verschwinden Navigation und Filter, jede Klasse beginnt auf einer neuen Seite und enthält einen kurzen Hinweis an die Klassenleitung.

## Klassenleitung

Jede Klasse hat ein Feld **Klassenleitung** (Freitext, unter „Schule und Schuljahre“). Die Klassenliste nennt sie über der Tabelle. Es ist bewusst kein Verweis auf ein Ausleihkonto, weil das Modul Schule keine Ausleihkonten kennt.

## Beispieldaten

`php artisan db:seed --class=SampleOperationsSeeder` legt (nur außerhalb der Produktion und nur einmal) Klassen mit Klassenleitung, 45 Schüler:innen und 3 Lehrkräfte (`S-10101…`, `L-20101…`), Schließtage (Herbst- und Weihnachtsferien), 16 Ausleihen auf vorhandenen Exemplaren (überfällig, bald fällig, laufend) sowie drei Vormerkungen an, davon eine abholbereit. Medien werden nicht erzeugt. Dazu bekommt das nächste Schuljahr passende Folgeklassen, damit sich der Schuljahreswechsel ausprobieren lässt.

## Offen

- Eine Seite zur Einsicht der Fristen und eines Probelaufs in der Oberfläche gibt es nicht; `privacy:anonymize --dry-run` zeigt die Zahlen.
- Auskunft nach DSGVO (Datenexport einer Person) ist nicht gebaut.
