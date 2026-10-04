# BiblioCollect

**BiblioCollect** ist die webbasierte VDBS-Schulbibliothekssoftware auf Laravel 13. Die Anwendung ist als modularer Monolith aufgebaut und trennt öffentliche Recherche, persönliches Portal, Bibliotheksbetrieb und Verwaltung in eigene Surfaces.

## Technische Basis

- PHP 8.4+
- Laravel 13
- Livewire 4
- Blade / Tailwind CSS 4 / Vite
- Pest, PHPStan/Larastan und Pint
- SQLite für lokale Entwicklung und Tests
- MySQL/MariaDB für Produktion

## Architektur

```text
Foundation
  ↓
Domain Modules
  ↓
Application Surfaces
  ↓
VDBS Design System / Presentation
```

Writes laufen über **Actions**, Reads über **Queries**, wiederverwendbare Fachlogik über **Services**. Module dürfen keine konkreten Surfaces kennen. Rollen sind kombinierbare Bündel stabiler Permissions.

### Surfaces

- `Public` – Katalog, Informationen, Veranstaltungen und öffentliche Listen
- `Portal` – persönliches Bibliothekskonto
- `Pos` – Ausleihe und Bibliotheksbetrieb
- `Administration` – Regeln, Importe, Datenschutz und Verwaltung

### Fachmodule

- Identity
- Patrons
- School
- Catalog
- Circulation
- Collection
- Reminders
- Acquisition
- Events
- Lists
- Content
- Privacy
- Audit

## Aktueller Projektstand

### T0 – Foundation

Abgeschlossen. Laravel-Basis, modulare Struktur, CI, Pint, PHPStan und Pest sind eingerichtet.

### T1 – Application Infrastructure

Abgeschlossen. Enthalten sind Permission- und Rollen-Registry, serverseitige Gates/Middleware, Navigation, vier Surfaces, VDBS-App-Shell, UI-Basiskomponenten und die fachliche Zeitzone `Europe/Berlin`.

### T2 – Identity, Patrons & School

In Umsetzung. Der aktuelle Stand enthält getrennte `User`- und `Patron`-Datensätze, persistierte kombinierbare Rollen, einmalige Codes zur Verknüpfung eines Onlinekontos mit einem Ausleihkonto, E-Mail-Verifikation sowie die ersten School-Modelle und den `SchoolCalendarService`.

Mit v0.3.2 kommt die erste echte Mitarbeiteroberfläche hinzu: gezielte Ausleihkontosuche, need-to-know Detailansicht, sichere Ausgabe der Onlinekonto-Einmalcodes und eine bewusst auf Schüler-AG-Rollen begrenzte Rollenverwaltung.

Die fachliche Dokumentation liegt unter `docs/`.

## Qualität

Vor einem Commit lokal ausführen:

```bash
php vendor/bin/pint --test
php vendor/bin/phpstan analyse --no-progress --memory-limit=1G
php artisan foundation:check
php vendor/bin/pest
npm run build
```

Alternativ bündelt `composer quality` die PHP-Qualitätsprüfungen.

## Lokaler Start

```bash
php artisan migrate
npm run dev
php artisan serve
```

## VDBS Design

BiblioCollect verwendet das VDBS-Farbsystem und lokal eingebundene Schriften. Die Oberfläche orientiert sich in ihrer Informationsarchitektur an etablierten Bibliothekskatalogen, bleibt visuell aber eine eigenständige VDBS-Anwendung.

## Datenschutz und Kontenmodell

Ein **Ausleihkonto (`Patron`)** ist kein **Onlinekonto (`User`)**. Bibliotheksnutzung bleibt ohne Login und ohne E-Mail möglich. Ein Onlinekonto wird nur über einen einmaligen, persönlich in der Bibliothek ausgegebenen Verknüpfungscode mit einem vorhandenen Ausleihkonto verbunden.
