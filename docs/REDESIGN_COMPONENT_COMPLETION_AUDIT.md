# Komponentenbibliothek – Abschlussstand der geplanten Runden A–D

**Status: Implementierung vollständig · Freigabe noch offen.**

Der ursprünglich gemeinsam definierte Prioritätenkatalog umfasst 24 zusätzliche Komponenten. Der Ausbau ist in der bestehenden Galerie `/design-lab/komponenten` auf dem Branch `feature/public-home-designsystem-redesign` und im **bestehenden Draft-PR #92** abgeschlossen. Diese Dokumentation bedeutet **keinen Merge und keine visuelle oder fachliche Freigabe**.

## Lieferumfang

| Priorität | Runden | Bausteine | Galerie |
|---|---|---:|---|
| A: Grundlegende anspruchsvolle Eingaben & komplexe Interaktion | K14/K15 | 8 | 59–66 |
| B: Bibliothek und persönliches Portal | K16 | 6 | 67–72 |
| C: Verwaltung und Formpatterns | K17 | 6 | 73–78 |
| D: Orientierung und Lesbarkeit | K18 | 4 | 79–82 |
| **Gesamt** | **K14–K18** | **24** | **59–82** |

### Einordnung

- Alle neuen Bausteine sind über eigenständige Blade-Komponenten nutzbar und in der Galerie mit Beispielzuständen dokumentiert.
- Gemeinsame VDBS-Tokens, Farbbedeutungen und Schriften bleiben erhalten. Dichtevarianten Comfortable/Compact sind dort vorgesehen, wo passend.
- Fachlogik (Vormerkung, Import, Jobausführung, Rechte, Persistenz, Termine, echte Bestätigungen) wurde **nicht** in die Komponentenbibliothek verlagert.
- Neue Interaktionsskripte betreffen nur optionale, lokale UI-Zustände; für einfache Navigation und Langtexte werden native HTML-Mechanismen verwendet.
- Die Formularbeispiele der Galerie bleiben ohne `<form>` und ohne speichernde Requests; echte Fachformulare bleiben dem späteren Integrationsschritt vorbehalten.
- Ein Regressionstest `ComponentLibraryCompletionTest.php` kontrolliert die 24 eindeutigen IDs samt Galerie-Navigation und die Abwesenheit von Formularen.

## Qualitätsgates

Die folgenden Gates entscheiden über die **Freigabe**, nicht über das Vorhandensein des implementierten Codes:

| Gate | Abschlussbedingung |
|---|---|
| Funktionale Featuretests | Neue und ältere Komponenten-Tests in SQLite und MariaDB grün; fehlerhafte Testausgaben untersuchen |
| PHP/CSS/JS-Build | Pint, PHPStan, Vite-Build und Quellformat ohne neue Fehler |
| Automatisierte Barrierefreiheit | Axe und mobile/tabletbezogene Layout-Prüfung ohne fehlschlagende Funde |
| Mobile und Zoom | 320/375px, Tablet, 200/400%-Zoom ohne abgeschnittene relevante Texte oder Bedienelemente |
| Tastatur | Tab/Fokus, Shift+Tab, Eingabefelder, Pfeiltasten, Enter, Escape; kein Verlust des Fokus |
| Assistive Technologien | Beschriftungen, Fehlermeldungen, Status, Dialoge/Listen mit realem Screenreader prüfen |
| Darstellung | Dark/Light, Comfortable/Compact, lange deutsche Texte, Disabled/Leer-/Fehlerzustände |
| Release | Gemeinsame Freigabe des Designs; bis dahin Draft-PR **nicht mergen** und keine produktiven Seiten ersetzen |

### Bekannte Befunde vor dem Abschluss

1. Der ältere `tests/Feature/OperationsCommandsTest.php` meldete wiederholt eine Abweichung der Cron-/Job-Ausgabe (Zeile 115) in beiden Datenbankvarianten. Dieser Test gehört **nicht** zur Komponentenbibliothek. Die Fachlogik wird in diesem Branch bewusst nicht verändert.
2. Die mobile Accessibility-Pipeline meldete zuletzt Überläufe beim gemeinsam verwendeten Account-Menü sowie eine schmale Tippfläche von `Mein Konto`. Die gemeinsamen CSS-Regeln im selben Komponenten-Branch wurden dafür angepasst; das Ergebnis der **neuen** Layoutprüfung ist ausschlaggebend.
3. Screenreader-Test und visuelle Freigabe im lokalen Browser können nicht durch PHP-Tests ersetzt werden.

**Offen bleibt die Freigabe, nicht die Implementierung des 24-Komponenten-Katalogs.** Änderungen an produktiven Fachseiten sind ein eigener, späterer Schritt.
