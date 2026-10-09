# BiblioCollect – Hinweise für Entwickler:innen und KI-Assistenten

BiblioCollect ist die Schulbibliothekssoftware des VDBS e. V. (Laravel 13, PHP 8.4, Pest, Pint, PHPStan Stufe 6, Vite, Tailwind). Sie läuft im Echtbetrieb auf Strato-Webspace **ohne SSH** (Cron per URL, Updates über die Seite „Verwaltung → Update“). Dieses Repo ist **öffentlich**.

Ausführlich: `CONTRIBUTING.md` (Zusammenarbeit, Pull Requests, Veröffentlichen), `docs/CONVENTIONS.md` (Code), `docs/ARCHITECTURE.md`, `docs/PROJECT_STATUS.md` (was gebaut ist), `docs/LOKALE_EINRICHTUNG.md`.

## Sprache

Deutsch für alles, was Nutzer:innen oder das Team lesen: Oberfläche, Hilfe (`resources/help/*.md`), Doku, Issues, Pull-Request-Titel, Commit-Nachrichten. Code, Klassen- und Methodennamen bleiben englisch. Nutzer:innen werden gegendert (Schüler:innen, Mitarbeiter:innen).

## Arbeitsablauf

- Nie direkt nach `main`. Branch von aktuellem `main` (`feature/<nr>-…`, `fix/<nr>-…`, `chore/…`), kleine Schritte, Pull Request mit Vorlage, **Squash-Merge**.
- Vor jedem Push: `composer quality` (Pint, PHPStan, `foundation:check`, Pest). Nach `git pull` oder Branch-Wechsel: `composer sync`.
- Pull Requests brauchen grüne Prüfungen `quality` und `mariadb`; ein Review der anderen Person ist erwünscht, aber kein Muss.
- Veröffentlicht wird nur per **Actions → Release** (Tag, ZIP, GitHub Release). Tags nie verschieben oder löschen.
- Zu jedem größeren Schritt gehören Tests, ein Abschnitt in `docs/PROJECT_STATUS.md` (nur den eigenen ändern) und, wenn Nutzer:innen es merken, ein Eintrag in `resources/help/`.

## Architektur

- Module unter `app/Modules/*` (Catalog, Circulation, Patrons, Identity, School, Audit, Privacy, Reminders, Content, …), Oberflächen unter `app/Surfaces/*` (Public, Portal, Pos, Administration). Module greifen nicht auf Oberflächen zu; `Foundation` importiert keine Module; Audit importiert nicht Patrons. `tests/Architecture` und `php artisan foundation:check` prüfen das.
- Geschäftslogik in Actions und Services, nicht in Controllern; Konstruktor-Injektion, explizite Rückgabetypen, Enums für feste Zustände, Transaktionen um mehrere Schreibvorgänge.
- Rechte stehen in `config/authorization.php`, Navigation in `config/navigation.php`, Vorgänge in `config/processes.php`.
- Migrationen nur anfügen und abwärtskompatibel halten; nach einem Release nie ändern. Eindeutige Zeitstempel.
- `config()` statt `env()` außerhalb von `config/`. Neue Einstellungen in `.env.example` dokumentieren.

## Hosting-Eigenheiten (kein SSH)

- Alles, was der Betreiber tun muss, braucht eine Web-Oberfläche oder einen Cron-Aufruf (`/_cron`, `/_setup`, `/_status`, Update-Seite). Zeitplan-Aufgaben laufen im selben Prozess (`Schedule::call`), nicht als eigener `php artisan`-Prozess.
- Lange Aufgaben in kleine Stücke teilen (Laufzeitgrenze des Anbieters), Fehler als Betriebsmeldung (`AlertService`) statt still.
- Shared Hosting hat kleine Upload-Grenzen: Pakete lassen sich auch per FTP in `storage/app/updates` legen.

## Sicherheit und Datenschutz

- **Nichts Geheimes oder Personenbezogenes committen**: keine `.env`, Tokens, Passwörter, echte Namen oder Klassenlisten, keine Datenbank- oder Sicherungsdateien. Das Repo ist öffentlich; Secret-Scanning ist aktiv.
- Personenbezogene Daten gehören in Auskunft (`PatronDataExport`) und Anonymisierung (`AnonymizationService`); neue Tabellen mit Personenbezug dort anschließen.
- CSV-Ausgaben immer über `CsvExport` (Schutz vor Formeln).

## Tests und Werkzeuge

- Pest mit SQLite im Speicher; die MariaDB-Variante läuft in der CI. Testdaten ohne echte Personen. Externe HTTP-Aufrufe mit `Http::fake()`.
- Blade: Kein Inline-`@if (…) Text @endif` mitten in einem Satz (führt zu Parserfehlern); Text vorher in `@php` oder als Ausdruck berechnen.
- Oberflächen bei 320, 375 und 768 px prüfen (kein seitliches Scrollen, Tippflächen mindestens 24 px), hell und dunkel.
- Windows-Entwicklung: Skripte (`scripts/*.ps1`) laufen mit `powershell -ExecutionPolicy Bypass -File …`.
