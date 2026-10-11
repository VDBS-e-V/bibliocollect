# Designatlas – Alle BiblioCollect-Vorlagen prüfen

## Aufruf

Nur in der lokalen und automatisierten Testumgebung:

```text
http://localhost:8000/design-review
```

Mit `APP_ENV=production` wird die Route absichtlich **nicht** registriert.

## Zweck

Der Atlas zeigt **alle zwölf vorgesehenen Seitentemplates** in einem Dokument, ergänzt um eine Komponentenübersicht. Es handelt sich um Designstudien; die jeweiligen Verwaltungs- und Betriebsvorlagen sind dadurch noch nicht vollständig in produktive Fachseiten integriert.

Die Seite ist ausdrücklich **keine Kopie echter Daten**:
- Titel, Personennamen, Mengen und Termine sind fiktiv;
- Aktionen sind deaktiviert und speichern nichts;
- echte Navigation und Benutzerrechte der App-Shell bleiben erhalten;
- die vier typografischen Covervarianten werden über `x-library.media-cover` angezeigt.

## Templateübersicht

| ID | Vorlage | Typische Anwendungsflächen | Stand |
|---|---|---|---|
| T01 | Startseite | Öffentlicher Einstieg | Redesign integriert |
| T02 | Suche & Filter | Öffentlicher/interner Katalog | Cover integriert, weitere Layoutanpassungen offen |
| T03 | Medien-Detailseite | Öffentliche Titeldetails | Cover integriert, Layout weiterzuentwickeln |
| T04 | Listen & Tabellen | Ausleihlisten, Benutzerverwaltung | Designbeispiel |
| T05 | Formulare | Medienerfassung und Einstellungen | Designbeispiel |
| T06 | Persönliches Portal | Ausleihen, Vormerkungen, Merkliste | Designbeispiel |
| T07 | Scanner & Ausleihe | Ausleihe/Rückgabe | Designbeispiel |
| T08 | Assistenten | Import, Inventur, Schuljahreswechsel | Designbeispiel |
| T09 | Sammlungen | Reihen, Leselisten, Empfehlungen | Designbeispiel |
| T10 | Dashboard | Betriebs- und Verwaltungsübersicht | Designbeispiel |
| T11 | Information & Hilfe | Anleitungen und FAQ | Designbeispiel |
| T12 | Druckvorlagen | Belege, Ausweise, Etiketten | Designbeispiel |

Die Komponentenübersicht zeigt die VDBS-Grundfarben, Schriften, Buttons, Status, Hinweise und alle vier Ersatzcovervarianten.

## Reviewablauf

1. Die Seite in einem Browser bei ca. 1440, 1024, 768, 390 und 320 Pixel ansehen.
2. Theme-Umschalter im gemeinsamen Header aktivieren und alle Abschnitte erneut prüfen.
3. Tastaturfokus, Kontraste, Zeilenumbrüche und horizontales Scrollen kontrollieren.
4. Änderungsvorschläge mit T-Nummer und konkreter Beobachtung notieren (z. B. „T02 Filter zu breit“, „T09 Schrift auf Buchcovern zu klein“).
5. Freigegebene Vorlagen schrittweise unter `resources/views/patterns/` bzw. `resources/views/templates/` als reale wiederverwendbare Laravel-Bausteine realisieren.

## Technische Dateien

- `app/Surfaces/Public/routes.php` – lokale/Test-Route
- `resources/views/pages/design-review.blade.php` – Vorschau
- `resources/css/patterns/design-review.css` – alle Atlasstile, bewusst `bc-review-`-präfixiert
- `tests/Feature/DesignReviewPageTest.php` – Route, Vollständigkeit und deaktivierte Demo-Aktionen
- Bestehende Komponenten unter `resources/views/components/ui/` und `resources/views/components/library/`

Keine Migrationen und keine Änderungen an Geschäftslogik oder Berechtigungsmodellen.
