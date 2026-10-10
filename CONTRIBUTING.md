# Zusammenarbeit

Wir sind zwei Personen. Damit jede Person arbeiten und veröffentlichen kann, ohne auf die andere zu warten, gilt:
**Nach `main` wird nicht mehr direkt committed.** Alles läuft über Pull Requests, und `main` ist immer in einem Zustand, den man veröffentlichen kann.

## Lokal einrichten und aktuell halten

Erstmalig: `docs/LOKALE_EINRICHTUNG.md`.

Nach jedem `git pull`, Branch-Wechsel oder Rebase:

```bash
composer sync
```

Das führt `composer install`, `npm ci`, `php artisan migrate` und `npm run build` aus. Ohne diesen Schritt hängen Datenbank und Oberfläche dem Code hinterher
(typische Zeichen: „no such table“-Fehler, ein sonderbares Design).

## Arbeiten in Branches

1. Zu jeder Aufgabe gibt es ein Issue (Vorlagen unter „New issue“).
2. Neuen Branch von einem aktuellen `main` anlegen, mit Issue-Nummer:
   - `feature/<nr>-kurzname` für neue Funktionen
   - `fix/<nr>-kurzname` für Fehlerbehebungen
   - `chore/<kurzname>` für Technik, Doku und Aufräumen
3. Kleine, abgeschlossene Schritte. Lieber mehrere kleine Pull Requests als einen großen.
4. `composer quality` lokal ausführen (Pint, PHPStan, `foundation:check`, Tests).
5. Branch pushen und einen Pull Request gegen `main` öffnen. Die Vorlage führt durch die Angaben. `Closes #<nr>` schließt das Issue beim Merge.

## Pull Requests

- Gemergt wird nur mit **Squash**. Der Titel des Pull Requests wird zur Zeile im Änderungsprotokoll: kurz, auf Deutsch, aus Sicht der Nutzer.
- Die Tests `quality` und `mariadb` müssen grün sein, und der Branch muss zu `main` aktuell sein (Knopf „Update branch“ oder Rebase).
- Ein Review der anderen Person ist erwünscht, aber **nicht Pflicht**. Niemand blockiert die andere Person. Bei Änderungen an Rechten, Datenschutz, Migrationen oder dem Update-Ablauf bitte trotzdem kurz zeigen.
- Nach dem Merge wird der Branch automatisch gelöscht.
- Wer eine Änderung der anderen Person in den eigenen Branch braucht, holt sie sich per Rebase auf `main`, nicht per Merge von `main` in den Branch.
- Force-Push ist nur auf eigenen, noch nicht geteilten Branches erlaubt (`git push --force-with-lease`).

## Unfertiges in `main`

Was in `main` ist, kann mit dem nächsten Release beim Betreiber landen, egal wer es veröffentlicht. Deshalb:

- Ein Pull Request wird erst gemergt, wenn die Funktion nutzbar ist, oder sie ist für Nutzer nicht erreichbar (hinter einem Recht oder nicht in der Navigation).
- Migrationen sind **abwärtskompatibel**: erst ergänzen, in einem späteren Release entfernen. Bestehende Migrationen werden nach einem Release nie verändert (`docs/CONVENTIONS.md`).
- Zeitstempel von Migrationen müssen eindeutig sein. Nach einem Rebase prüfen, dass die Migration der anderen Person nicht denselben Zeitstempel trägt, und die eigene gegebenenfalls umbenennen (vor dem Merge, nie danach).

## Konflikte vermeiden

- `docs/PROJECT_STATUS.md` hat sehr lange Zeilen und ist der häufigste Konfliktherd. Nur den eigenen Abschnitt ändern, bei einem Konflikt den Branch auf `main` rebasen und die Stelle von Hand zusammenführen.
- Vor größeren Umbauten kurz absprechen, wer welche Module anfasst (Issue-Kommentar genügt).

## Veröffentlichen

Veröffentlicht wird ein Stand von `main`. Jede Person kann das jederzeit tun:

1. Auf GitHub: **Actions → Release → Run workflow**.
2. Eingeben: `version` (zum Beispiel `v0.60.0`, muss größer sein als der neueste Tag; die Update-Seite vergleicht Versionen) und optional `ref`
   (Standard `main`; ein älterer Commit auf `main`, wenn neuere Änderungen noch nicht mitkommen sollen).
   Mit `dry_run` wird nur gebaut und geprüft, ohne Tag und Release.
3. Der Workflow prüft Version, Stand und grüne Tests, baut das Paket (`scripts/build-release.ps1`), legt den Tag an und erstellt ein GitHub Release mit
   dem ZIP (`bibliocollect-<version>.zip`), seiner Prüfsumme (`bibliocollect-<version>.zip.sha256`) und automatisch erzeugten Notizen aus den Pull-Request-Titeln.
   Die Update-Seite beim Betreiber holt das Paket von dort selbst („Neue Version von GitHub holen“), ohne Upload im Browser.
4. Das ZIP wird beim Betreiber unter **Verwaltung → Update** eingespielt (`docs/WEBSPACE_UPLOAD.md`).

Tags werden nie verschoben oder gelöscht. Ein fehlerhaftes Release wird durch ein neues mit höherer Version ersetzt.

## Was GitHub erzwingt

Die Regeln stehen als Dateien in `.github/rulesets/` und werden von einer Admin-Person mit `scripts/apply-github-settings.ps1` angewendet (legt Rulesets an oder aktualisiert sie, setzt die Merge-Optionen und die Sicherheitsfunktionen). Änderungen an den Regeln laufen deshalb ebenfalls über einen Pull Request.

- `main` (Ruleset `main`): nur per Pull Request, Prüfungen `quality` und `mariadb` grün und Branch aktuell, nur Squash-Merge, kein Force-Push, kein Löschen, lineare Historie. Kein Pflicht-Review. Ausnahmen kann nur eine Admin-Person und nur über einen Pull Request setzen (Hotfix).
- Tags `v*` (Ruleset `release-tags`): dürfen nicht gelöscht oder verschoben werden. Angelegt werden sie vom Workflow „Release“.
- Merge-Optionen: nur Squash, Branch wird nach dem Merge gelöscht, „Update branch“ ist verfügbar.
- Sicherheit: Secret-Scanning mit Push-Schutz (das Repo ist öffentlich) und Dependabot-Warnungen.

## Abhängigkeiten (Dependabot)

Dependabot öffnet montags Pull Requests für Composer, npm und GitHub Actions; kleine Updates sind gebündelt. Ein solcher Pull Request wird wie jeder andere behandelt: Prüfungen grün, kurz in `docs/PROJECT_STATUS.md` oder der Release-Notiz erwähnen, wenn Nutzer:innen etwas merken. Große Sprünge (Laravel, PHP, Vite) einzeln, mit lokalem `composer quality` und einem Blick auf die Oberfläche (`npm run build`).

## Wenn der Branch zu `main` nicht mehr aktuell ist

Auf dem Pull Request den Knopf **Update branch** drücken (oder lokal `git fetch && git rebase origin/main`, dann `git push --force-with-lease`). Danach laufen die Prüfungen neu.
