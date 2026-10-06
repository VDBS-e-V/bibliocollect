# Protokoll und Öffnungszeiten (v0.5.3)

## Öffnungszeiten und Schließtage

Unter `/verwaltung/oeffnungszeiten` (Navigation „Öffnungszeiten“, Recht `school.manage`, also Verwaltung) pflegt die Schule, wann die Bibliothek geöffnet hat. `SchoolCalendarService` liest diese Daten für Fälligkeiten, Verlängerungen und Abholfristen.

- Wochenübersicht: je Wochentag geöffnet ja/nein und **ein oder mehrere Zeiträume**, z. B. 08:00–10:00 und 13:00–15:00. Die Zeiträume eines Tages dürfen sich nicht überschneiden, das Ende muss nach dem Beginn liegen; leere Felder werden ignoriert, und nach dem Speichern steht wieder ein freier Platz für einen weiteren Zeitraum bereit. Mindestens ein Tag muss geöffnet sein, sonst ließe sich keine Fälligkeit berechnen. Für Fristen zählt nur, ob ein Tag überhaupt Öffnungszeiten hat.
- Schließtage: Einzeltag oder Zeitraum (höchstens 400 Tage) mit optionalem Grund. Bereits eingetragene Tage bleiben unverändert, das Eintragen ist wiederholbar. Einzelne Tage lassen sich entfernen. Die Liste zeigt standardmäßig nur kommende Schließtage.
- Bereits vergebene Fälligkeiten ändern sich nicht rückwirkend. Neue Ausleihen, Verlängerungen und Abholfristen berücksichtigen die Einträge sofort.

## Protokoll (Audit)

Das Modul `Audit` speichert unveränderliche Ereignisse in `audit_events` (Zeitpunkt, handelnde Person, Aktion, Gegenstand, Beschreibung, Kontext). Es gibt kein Bearbeiten und kein Löschen in der Oberfläche.

Aufgezeichnet werden:

| Aktion | Auslöser |
|---|---|
| `circulation.loan.checked_out` / `.returned` / `.renewed` | Ausleihe, Rückgabe, Verlängerung |
| `circulation.reservation.placed` / `.ready` / `.fulfilled` / `.cancelled` / `.expired` | Lebenslauf einer Vormerkung |
| `catalog.intake.recorded` / `catalog.copy.added` | Erfassung eines Mediums bzw. weiteres Exemplar |
| `catalog.metadata.applied` | Übernahme eines Metadatenvorschlags (Felder, Quelle) |
| `school.opening_hours.updated` / `school.closures.created` / `school.closure.deleted` | Änderungen am Kalender |

Grundsätze:

- Ereignis und Fachänderung entstehen in derselben Transaktion. Scheitert die Fachänderung, gibt es kein Ereignis; scheitert das Ereignis, gilt die Änderung nicht.
- Der Kontext enthält nur Kennungen und Fachwerte (IDs, Barcodes, Daten), keine Namen oder Freitexte von Personen. Beschreibungen nennen Exemplare, nicht Personen.
- `actor_user_id` hat bewusst keinen Fremdschlüssel, damit das Protokoll das Löschen eines Kontos überdauert. Ohne angemeldete Person (Scheduler) steht „System“.
- Sperren, Entsperren und Austritte von Ausleihkonten haben bereits eigene Ereignistabellen im Modul Patrons und werden nicht doppelt geführt.

Einsicht unter `/verwaltung/protokoll` (Recht `audit.view`, nur Verwaltung), filterbar nach Bereich und Suchbegriff, 50 Einträge je Seite.

## Offen

- Aufbewahrungs- und Löschfristen für das Protokoll und für abgeschlossene Ausleihen gehören ins Modul `Privacy` und brauchen eine Entscheidung der Schule.
- Weitere Ereignisse (Rollenzuweisungen, Katalogbearbeitung einzelner Felder, Aussonderung) lassen sich über `AuditRecorder::record()` ergänzen.
