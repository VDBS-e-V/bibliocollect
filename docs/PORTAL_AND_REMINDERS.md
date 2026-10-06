# Portal-Selbstbedienung und Erinnerungen (v0.5.4)

## Portal „Mein Konto“

`/konto` zeigt Personen mit verknüpftem Onlinekonto (Recht `surface.portal.access`) ausschließlich ihre eigenen Vorgänge:

- **Aktuelle Ausleihen** mit Fälligkeit, Status („Überfällig“, „n-mal verlängert“) und der Aktion „Verlängern“. Ist eine Verlängerung nicht erlaubt, steht der Grund da (Höchstzahl, überfällig, Vormerkung). Es gelten dieselben zentralen Regeln wie im Bibliotheksbetrieb (`CirculationRuleEvaluator::renewalViolations()`).
- **Vormerkungen** mit Status („Wartet“ mit Position, „Abholbereit“ mit Frist) und „Stornieren“.
- Ohne verknüpftes Ausleihkonto erscheint ein Hinweis, wie man es verknüpft (Code aus der Bibliothek).

Vormerken geht von der öffentlichen Titelseite aus: Sind alle Exemplare ausgeliehen, erscheint „Titel vormerken“ (angemeldet und verknüpft), „Vorgemerkt“ mit Link ins Portal, oder der Hinweis zum Anmelden. Ist ein Exemplar verfügbar, wird nicht vorgemerkt.

Schutz:

- Jede Portal-Aktion wirkt nur auf das Ausleihkonto des angemeldeten Kontos. Fremde Ausleihen oder Vormerkungen liefern 404, ein Konto ohne Verknüpfung 403.
- Dieselben Aktionen wie im Betrieb (`RenewLoanAction`, `PlaceReservationAction::executeForTitle`, `CancelReservationAction`) laufen mit Transaktion, Sperren und Protokoll (Audit); die handelnde Person ist das Onlinekonto selbst.
- Eine Lehrkraft sieht nur ihr eigenes Ausleihkonto, nie die Ausleihen von Schüler:innen.

## Erinnerungen

`php artisan reminders:send` (Scheduler täglich 07:00 Uhr) schickt E-Mails an **bestätigte, verknüpfte Onlinekonten**:

| Art | Wann |
|---|---|
| Rückgabe bald fällig | `due_soon_days` (Standard 2) Tage vor der Fälligkeit |
| Rückgabe überfällig | am ersten Tag nach der Fälligkeit, dann alle `overdue_interval_days` (Standard 7) Tage |
| Vormerkung abholbereit | sobald ein Exemplar zurückgelegt ist, mit Abholfrist |

- Jede Erinnerung geht je Ausleihe/Vormerkung und Stufe nur einmal raus (`reminder_logs`, eindeutig). Schlägt der Versand fehl, wird der Eintrag zurückgenommen und der nächste Lauf versucht es erneut.
- Die Mail nennt nur Titel und Termine der eigenen Medien. Ausleihkonten ohne Onlinekonto oder mit unbestätigter E-Mail-Adresse bekommen nichts; für sie bleibt die Rückgabe-Kontrolle am Arbeitsplatz.
- Konfiguration: `config/reminders.php`. Der Versand nutzt den Standard-Mailer (`MAIL_MAILER`); im Entwicklungsbetrieb schreibt `log` die Mails in `storage/logs`. Für den Produktivbetrieb sind SMTP-Daten in `.env` nötig.

## Offen

- Mahnungen mit Gebühren und Mahnbriefen bleiben eine Entscheidung der Schule (Modul-Beschreibung nennt sie, sie sind nicht gebaut).
- Eine Einstellung „keine Erinnerungen“ je Konto gibt es noch nicht.
- Benachrichtigungen im Portal selbst (statt E-Mail) sind nicht umgesetzt.
