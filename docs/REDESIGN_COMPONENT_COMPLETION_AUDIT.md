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

## Abschluss-Fixrunde – Mobile/A11y

- 110 automatische Axe-Seitenprüfungen im letzten Lauf: 0 Verstöße mit Schweregrad serious oder höher; der separate Layoutcheck schlug bei 1024px wegen einer dekorativen Hero-Kreisfläche fehl (12px Überstand). Der visuelle Hero-Baustein beschränkt diese Kreisposition nun ab Tabletbreite, **ohne** die Startseiten-View oder deren Inhalte zu bearbeiten.
- Kleinere Linkflächen werden in gemeinsamen Shell-/Visual-Styles vergrößert. Verborgene Account-Menü-Inhalte sind im geschlossenen Zustand auch für geometrische Layoutchecker vollständig ausgeblendet.
- Die Wirkung muss durch **den nächsten CI-Lauf** bestätigt werden. Hinweise zu den verbleibenden kleinen Linkflächen auf anderen bereits vorhandenen Seiten werden getrennt dokumentiert.

## Browser-Interaktionen (zusätzlicher Abschluss-Test)

Mit `npm run check:components` werden unter Chrome mit der bestehenden Designlab-Galerie sechs echte Client-Interaktionen geprüft:

1. Combobox: Vorschläge per Tastatur und synchronisierter nativer Select-Wert.
2. Data-Grid: Select-all berücksichtigt deaktivierte Zeilen, Zähler aktualisiert sich.
3. Command-Palette: Dialog-/Filter-/Escape-Zustände.
4. Repeater: Hinzufügen und Entfernen mit indizierten Formularnamen.
5. Transferliste: Verschieben erzeugt korrekt synchronisierte versteckte Formwerte.
6. Read-More: Anriss vor dem Öffnen sichtbar, bei geöffnetem Volltext verborgen.

Der Test ist an den Accessibility-Workflow gekoppelt und benötigt Chrome und den laufenden Laravel-Demoserver; er hat keine schreibende Serveraktion. Er ist zusätzlich zu Axe, dem Layoutcheck und den PHP-Featuretests zu bestehen.

Die öffentliche `/design-lab/komponenten`-Galerie ist nun zusätzlich in `scripts/support/site.mjs` für **alle vorhandenen Axe- und mobilen 320/375/768/1024-Pixel-Layout-Prüfungen** registriert. Auch alte Komponenten werden so automatisch auf ihre reale Darstellung geprüft. Falls der Prüflauf weitere Befunde sichtbar macht, bleibt die Freigabe bis zur Nachbearbeitung offen.

### Konkrete Befunde der erweiterten Galerieprüfung (nach K18)

Der erste vollständige Browserlauf fand ausschließlich in der **neu aufgenommenen Galerie** zwei ernsthafte Kontrastbefunde bei einer deaktivierten Kontaktpräferenz (Container-Opacity 0,65) und Mobilüberläufe bei Aktionsmenü, Kapitelmenü und langer Property-Beschreibung. Außerdem zeigte der neue Interaktionstest, dass Escape im nativen Suchfeld der Command-Palette den Dialog nicht zuverlässig schloss. Die genannten Ursachen wurden in den **gemeinsamen Komponenten-/Galerie-Styles und Interaktionsskripten** korrigiert. Diese Korrekturen erfordern erneut einen CI-Durchlauf; grüne Resultate dürfen erst nach dessen Abschluss eingetragen werden.

### Dark-Mode-Kontrast (letzter ernsthafter Axe-Befund)

Im erneuten Lauf bestanden die sechs echten Browser-Interaktionstests und der gesamte Layoutcheck mit **0 Fehlern bei 220 Seitensichten**. Die helle Axe-Prüfung war ohne schwere Verstöße. Im Dunkelmodus meldete Axe drei Textkontraste: zwei `bc-message-preview__eyebrow` (dunkelgrün auf dunklem Grund) und `bc-choice-cards__error` (dunkelrot auf dunklem Grund). Die Nachrichtenvorschau verwendet im dunklen Theme nun das **vorhandene VDBS-Grün**; der Fehlertext verwendet kontrastreiches `--text-primary` mit **vorhandenem VDBS-Rot** als Randakzent. Keine Markentokens verändert. Das Ergebnis des Folge-Workflows bleibt abzuwarten.

### Einheitliche kontrastreiche Fehlermeldungen

Der detaillierte Axe-Report zeigte zusätzlich zum K12-Auswahlkartenfehler auch den K14-Combobox-Fehlertext; weitere gleichartig formatierte `--vdbs-error`-Kleintexte sind in K14, K16 und K17 vorhanden. Ein gemeinsamer Dark-Mode-Override in `foundation-components-v18.css` setzt für **alle sechs betroffenen Error-Textklassen** `--text-primary` und verwendet das existierende `--vdbs-error` unverändert als linken Randakzent. Damit sollen sämtliche gleichartigen textlichen Kontrastfehler ohne Brand-Token-Änderung beseitigt werden.

### Abschlussprüfung vom 12. Oktober 2026 – verifizierter Stand vor letzter JS-Nachbesserung

- GitHub-Run `38285518078`, **Barrierefreiheit: erfolgreich**. Damit sind die zuvor protokollierten A11y-/Mobilfehler im automatisch geprüften Umfang behoben.
- Quality `38285518002` ist **nicht grün**; alle neuen Komponenten- und Completion-Tests bestehen. SQLite: **920 bestanden, 1 übersprungen, 1 fehlgeschlagen**; MariaDB: **916 bestanden, 5 übersprungen, 1 fehlgeschlagen**. Beide einzigen Ausfälle sind derselbe fachfremde `OperationsCommandsTest.php:115`.
- Interaktionsschutz: Der asynchrone K14-Combobox-Loader verwirft zusätzlich Antworten veralteter Suchanfragen per Abfrageversion. Der neue Commit muss den CI-Lauf erneut durchlaufen.
- Für die **Freigabe** bleiben manuelle Screenreader-/Zoom-/Designabnahme und die unabhängig zu klärende Cron-Testabweichung offen. Der Designsystem-Umfang ist implementiert; PR #92 bleibt Draft.

### Getrennter Komponenten-Check

Ein eigener PR-Workflow `Komponentenbibliothek` führt **nur** `tests/Feature/ComponentLibrary*Test.php` unter SQLite aus und erstellt zusätzlich das reguläre Vite-Bundle. Er läuft parallel zum unveränderten allgemeinen Quality-Workflow. Ein grüner Komponenten-Check ist **kein Ersatz** für den Gesamttest, die zweite Datenbank, den erfolgreichen Accessibility-Check oder die manuelle Freigabe; er erlaubt nur die getrennte Diagnose des fachfremden `OperationsCommandsTest`.

Letzter überprüfter unveränderter Gesamtstand vor diesem Workflow: Accessibility `38298666584` **success**, Quality `38298666694` **failure** ausschließlich bei `OperationsCommandsTest.php:115`. SQLite **920 bestanden / 1 übersprungen / 1 fehlgeschlagen**, MariaDB **916 bestanden / 5 übersprungen / 1 fehlgeschlagen**. Alle K14–K18-/Completion-Tests sind dabei bestanden.
