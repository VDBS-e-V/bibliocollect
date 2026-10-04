# BiblioCollect

**BiblioCollect** ist die webbasierte VDBS-Schulbibliothekssoftware auf der modularen Laravel-Foundation.

## Technische Baseline

- PHP 8.4+
- Laravel 13
- Livewire 4
- Blade
- Tailwind CSS 4
- Vite
- Pest 5
- Pint
- PHPStan/Larastan
- SQLite lokal
- MySQL/MariaDB Produktion

## Produkt- und Markenidentität

- Programmname: **BiblioCollect**
- Absender/Marke: **VDBS**
- UI-Basis: VDBS Design System
- Light Mode ist Standard; Dark Mode wird bewusst über einen UI-Umschalter aktiviert.
- Public/Portal übernehmen etablierte Bibliotheks-UX-Muster wie starke Suche, Trefferlisten, Filter/Facetten und Kontozugang, bleiben aber visuell eigenständig im VDBS-System.

Die Farbwerte liegen zentral in `resources/css/tokens.css`. Fach- und Komponenten-CSS verwendet semantische Rollen statt verstreuter HEX-Werte.

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

Writes laufen über **Actions**, Reads über **Queries**, wiederverwendbare Fachlogik über **Services**. Module dürfen keine konkreten Surfaces kennen. Rollen werden als Bündel zentraler Permissions umgesetzt.

### Surfaces

- `Public`
- `Portal`
- `Pos`
- `Administration`

### Geplante Fachmodule

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

## Lokaler Start nach Bootstrap

```bash
composer quality
npm run dev
php artisan serve
```

## Fonts

Die Font-Binaries werden nicht mit diesem Bootstrap-Paket ausgeliefert. Wenn die beiden Originalarchive lokal vorliegen, übernimmt `php install_fonts.php` die für die Webapp benötigten Dateien.

Erwartete Archive im Repository-Root:

- `Lato,Source_Serif_4.zip`
- `Neuland_Font_2017(1).zip`

## Projektstatus

Bootstrap v0.1.1 richtet **T0 und die strukturelle Basis von T1** ein und setzt den Produktnamen **BiblioCollect** sowie die VDBS-Web-Designbasis. Fachliche Models, Authentifizierungsworkflows und Bibliotheksprozesse folgen in den nächsten Paketen.
