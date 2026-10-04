# BiblioCollect Bootstrap v0.1.6

Dieses Paket ersetzt v0.1.5.

## Fixes

- Behebt den PHPStan-Fehler im generierten `FoundationValidator`: Die lokalen Arrays des Zyklusdetektors erhalten jetzt explizite Typen (`list<string>` bzw. `array<string, true>`), sodass `array_search()` korrekt als `int|false` analysiert wird.
- Bei einem bereits bestehenden BiblioCollect-Projekt spielt der Bootstrap diesen **gezielten Kompatibilitäts-Patch** automatisch in `app/Foundation/Validation/FoundationValidator.php` ein. Ein Löschen oder Neuaufsetzen des Projekts ist nicht nötig.
- Pint läuft danach weiterhin im Fix- und Prüfmodus; anschließend folgen PHPStan und Pest.
- Versionsmetadaten wurden auf v0.1.6 synchronisiert.

## Bereits erfolgreich bestätigt

Aus dem bisherigen Windows-Lauf funktionieren bereits:

- Laravel-13-Skeleton und Composer-PHAR-Auflösung
- Composer-Installation und Package Discovery
- SQLite und Migrationen
- direkter npm-Aufruf über `node.exe` + `npm-cli.js`
- Vite Production Build
- `foundation:check`
- Pint Auto-Fix und `pint --test`

## Bestehender Scope

- Produktname: **BiblioCollect**
- VDBS als sichtbare Anwendungsmarke
- Laravel 13 / PHP 8.4+ / Livewire 4 / Tailwind 4 / Vite / Pest
- Foundation + Surfaces + Modulmanifeste
- VDBS semantische Farb-Tokens und manueller Light/Dark-Modus
- Optionaler lokaler VDBS-Fontimport
- T0 / frühes T1; noch keine fachlichen Workflows
