# BiblioCollect – Komponentenreview K8

**Stand:** Entwurf vor visueller und barrierefreier Freigabe. Dieses Dokument ist eine Checkliste; nichts daran bestätigt eine bereits durchgeführte manuelle Prüfung. Farben und Schriftfamilien folgen unverändert den VDBS-Foundations.

## Aktive Vorschau

Lokal: \`http://localhost:8000/design-lab/komponenten\`. Die Navigation ist in sieben Kapitel gegliedert und führt zu den vorhandenen Blade-Komponenten. Alte Musterseiten tragen weiterhin „Älterer Entwurf“. **Finale Fachseiten entstehen erst nach Freigabe der kompletten Komponentenbibliothek.**

## Ergänzung K9: Kapitel 39–43

Im selben lokalen Designlab `/design-lab/komponenten` prüfen:

| Abschnitt | Zu prüfen | Ziel |
|---|---|---|
| 39 Datumsbereich | Gleiche Feldhöhe, Label/Hint/Error, 320px, natives Datumspicker-Verhalten | Felder bleiben ausgerichtet; Fehler sind als Text und mit `aria-describedby` zugänglich; keine vorgetäuschte dynamische Fachvalidierung |
| 40 Segmentierte Auswahl | Tastaturpfeile, 3–4 Optionen, Disabled, Auswahlkontrast, 320px | Native Radiogruppen, kein JS nötig; bei Mobilbreite sinnvoller Umbruch |
| 41 Datensatzkarte | Langer deutscher Titel, Metadaten, Status, Actions, Compact/Comfortable | Kein Abschneiden bei 320px, semantisches `dl`, berechtigte echte Links nur im Fachkontext |
| 42 Statuslegende | Grün, Lila, Orange, Rot und neutral in hell/dunkel | Status immer **schriftlich** erklärt, auch ohne Farbwahrnehmung |
| 43 Leere Ergebnisse/Pagination | Kein Treffer, eine Seite, mehrere Seiten | Vorhandene Komponenten kombiniert, keine doppelten Implementierungen |

Zusätzlicher konkreter Axe-Fund aus vorheriger CI: `.bc-footer-brand small` hatte ca. 3,98:1 Kontrast auf dunklem Grund. Diese Opazität wurde zugunsten von vollem VDBS-Weiß entfernt; erneuter Axe-Lauf bleibt erforderlich.

**Separater Layoutbericht:** Die letzte vollständige Breitenprüfung meldete häufig Überläufe am bereits bestehenden `.bc-account-menu__panel` bei schmalen Bildschirmgrößen (z. B. 375px mit linker Position kleiner als 0). Dieses produktive Kontomenü ist nicht Bestandteil der neuen K9-Komponenten und wird im nächsten gezielten Shell-/Responsive-Review separat behoben. Weitere Kleinst-Tippflächen auf produktiven Fachseiten bleiben offen. Diese Befunde dürfen nicht als durch K9 gelöst bezeichnet werden.

## Neue Reviewkapitel 32–38

| Abschnitt | Zu prüfen | Sollverhalten |
|---|---|---|
| 32 Toolbars/Mobilnavigation | Comfortable/Compact, Textumbruch, Tab/Enter/Escape, Außenklick | Links sind echte Anker; Disabled nicht ausführbar; Fokus sichtbar |
| 33 Filterpanel | native Auf-/Zuklappbarkeit, Filterlabels, schmale Breite | Native Details; Demo-Checkboxen ohne angebundene Query oder irreführenden Reset |
| 34 Textarea/File-Drop | Maxlength-Zähler, längere Texte, Tastatur, Auswahl von 0/1 Datei, falscher Dateityp | Kein Upload, kein Speichern; Dateiname/Fehler als Text; Hilfetexte verknüpft |
| 35 Timeline | Datum/Zeit, Umbruch, Farben | \`ol\`/ \`time\`; verständlich auch ohne Farben |
| 36 Datenbalken | Kontrast der VDBS-Farben, lange Kategorienamen, Zahlen | Grafik dekorativ; reguläre HTML-Datentabelle immer sichtbar |
| 37 Busy-Panel | Busy/Ready, Compact, Reduced-Motion | \`aria-busy\`, lesbarer Status, kein erfundener Prozentwert |
| 38 Review-Matrix | Screenwidth, Zoom, Tastatur, Theme, Screenreader | Nur lokale Checkliste ohne Speicherung/Abnahmeversprechen |

## Konkreter manueller Durchgang

1. Branch aktualisieren, Assets neu bauen: \`git pull --ff-only && npm run build\`. **Kein** \`php artisan serve\` – der lokale Server läuft bereits.
2. Bei **320 und 375 px** prüfen: Kapitelmenüs und Filter öffnen, Toolbar-Aktionen umbrechen, lange Schrift und Dateinamen testen, Diagrammtabelle lesen. Kein horizontales Abschneiden durch Komponenten.
3. Bei **768 und 1280 px** Comfortable/Compact vergleichen, insbesondere Toolbar-Aktionen und Datenbalken.
4. Im **Dunkelmodus** die neuen Komponenten, fokussierte Felder, Textkontrast, grüne/lila/orange Datenfarben und Badge-Beschriftungen kontrollieren.
5. Bei **200 % und 400 % Zoom** Labels, Statusmeldungen, Datenanzeige und Komponentennavigation auf Überläufe prüfen.
6. **Tab, Shift+Tab, Enter, Space, Escape**: Mobile-Navigation, Filterpanel, Dateiauswahl, Dialoge und Popovers bedienen. Sichtbarer Fokus und verständliche Reihenfolge sind verpflichtend.
7. Mit Screenreader (oder geeignetem Accessibility-Inspector) die Beschreibung von Datei-Inputs, den Zeichenzähler, die Busy-Statusmeldungen und die Datentabelle prüfen. **Die aktuelle CI allein ersetzt das nicht.**
8. Ergebnisse direkt als konkrete Komponentenkorrekturen festhalten. Komponenten erst nach umfassendem Review als freigegeben markieren.

## Fachliche Grenzen

- Opt-in Dateiauswahl und Dropzone zeigen **nur** eine lokale Auswahl an. Dateiendung/MIME-Prüfung im Browser ist keine Sicherheitsschranke; Upload und Validierung erfordern spätere Fachintegration.
- Ein geöffneter Filterbereich verändert keine Katalogdaten. Filterzählung, Reset-Links und echte Resultate müssen durch die Anwendung bereitgestellt werden.
- Balkendiagramme zeigen fiktive Daten; keine neue Statistik-API.
- Auch die bestehenden Designlab-Musterseiten sind weiterhin keine endgültigen produktiven Templates.
- Eine gesonderte projektweite CI-Störung bei \`OperationsCommandsTest.php\` (Cron/Job-Test) wurde bereits zuvor beobachtet; sie ist von Komponentenergebnissen zu unterscheiden, aber darf beim Merge nicht verschwiegen werden.

## Noch offene große Komponenten-Aufgaben

Eine produktionsfähige asynchrone Combobox mit Tastaturführung und geladenen Vorschlägen, robuste Overlays bei engen Bildschirmrändern, dynamische Feedbackregionen, serverseitiges Paging/Sorting und Filterpersistenz, Integration von Scannerhardware, echtes Drucklayout und datenschutzsicherer Nachrichtenausgang sowie vollständige Browser-/Accessibility-Abnahme sind noch nicht erledigt. Sie werden getrennt priorisiert, statt als „fertig“ zu gelten.

## Ergänzender Komponentenreview K11 (Abschnitte 48–51)

- **48 Ergebnishilfe:** Leere Suche, technische Fehlermeldung und nicht verfügbarer Bereich klar unterscheiden. Nur echte Links anklickbar; bestehende Empty-State-/Alert-Stile unverändert.
- **49 Abschnittsindex:** Tab, Enter, Shift+Tab und Fragmentanker testen. aria-current=location nach Hash-Wechsel am passenden Link; deaktivierte Einträge ohne Ziel.
- **50 Prüfstand:** Geprüft / In Bearbeitung / Prüfung nötig / Noch offen auch ohne Farbe verständlich; lange Titel, fehlende Links und leerer Prüfstand.
- **51 Änderungsvergleich:** Vorher/Nachher mit Screenreader und dl/dt/dd, Nullwerte, hinzugefügt/entfernt, 320px und 400%-Zoom prüfen.

Keine dieser Prüfaufgaben ersetzt die praktische Browser-/Screenreader-Abnahme oder ist eine Freigabe.

## Ergänzender Komponentenreview K12 (Abschnitte 52–54)

- **52 Auswahlkarten:** native Radio-/Checkbox-Tastaturführung, Klickfläche, `checked`, `disabled`, sichtbarer Fokus; lange Titel bei 320px; Hinweise und Fehler über `aria-describedby`; keinerlei Persistenz.
- **53 Dateianlagen:** Sonderzeichen und sehr lange Dateinamen, Größenangaben, Disabled ohne Scheinlink, 320px, echte Anker. Keine Speicherung und kein Dateiupload.
- **54 Prüfkriterien:** Status Erfüllt/Nicht erfüllt/Noch ungeprüft/Nicht anwendbar ohne Farbsehen unterscheiden, Compact/Comfortable und Leerzustand, Screenreader und Dunkelmodus.

Der bestehende Accessibility-CI-Lauf auf K11 meldete weiterhin einen fehlgeschlagenen mobilen/Tablet-Layoutcheck. K12 ist dadurch nicht freigegeben; neue CI und manuelle Prüfung bleiben erforderlich.

## K14/K15 – Priorität A, Reviewabschnitte 59–66

- **59 Combobox:** Select ohne JS; mit JS Trefferfilter, Pfeiltasten, Enter/Escape, deaktivierte Optionen, sichtbarer Fokus, Remote-Laden/Fehler und Abbruch bei Eingabefolge; Prüfen der Eingabebeziehung zum Label und Tastaturnutzung im Screenreader.
- **60 Datum/Uhrzeit:** native Picker unter Windows, Android, iOS; Format, min/max und Screenreader, Error/Hint unter dem Feld.
- **61 Zeitfenster:** Fieldset/Legend, zwei getrennte Input-Labels, 320px, Tastatur; fehlender Server-Endzeitcheck ist explizit keine UI-Garantie.
- **62 Formularaktionen:** Primary/Secondary, Cancel/Back als echte URLs, disabled Demo, Compact sowie Mobile-Umbruch.
- **63 Command-Palette:** Dialog öffnen, Strg/⌘+K außerhalb von Formularfeldern, Filter, Pfeile, Enter, Escape, Fokus-Rückgabe, Disabled ohne Pseudolink.
- **64 Data-Grid:** Tabellenköpfe, Checkbox-Select-all, teilselektierter Zustand, deaktivierte Zeile, 320px Scrollfokus, Sortierlink und Statusanzeige. Keine Query im Browser.
- **65 Side-Panel:** Focus-Trap über nativem Modal, Escape/Close/Rückfokus, Scrollen bei hoher Zoomstufe, Mobile und Dunkelmodus.
- **66 Confirmation-Flow:** Checkbox und ggf. exakte Phrase, Vorschau ohne POST, reale POST nur durch übergebene URL plus CSRF; Prüfung der serverseitigen Autorisierung und Validierung erst bei Fachintegration.

**Status:** Entwurf, nicht freigegeben. Automatisierte Tests und Browser-/Screenreaderprüfung getrennt erfassen; bekannte bestehende Layoutüberläufe und Cron-Test separat verfolgen.

## K16 – Priorität B, Komponenten 67–72

- **67 Mediendetails:** vorhandene Cover-/Verfügbarkeits-/Metadaten-APIs wiederverwenden; lange Titel bei 320/375 px, Compact, Dunkelmodus, Coverfallback, Actions-Fokus.
- **68 Vormerkungen:** waiting/ready/paused/expired/cancelled/unknown in Farbe und Text klar unterscheiden; Position nur bei waiting, Abholfrist nur bei ready; keine fiktive Fristberechnung.
- **69 Ausleihen:** bestehende 7-Tage-Grenze (7, 0, überfällig, unbekannt), lange Titel, Links, Rückgabedatum und alle Dichten prüfen.
- **70 Kennungen:** `dl/dt/dd`, lange ISBN/Barcodes ohne Überlauf, leere Werte, nur echte Links, keine automatische Validierung.
- **71 Standortpfad:** Standortreihenfolge auch ohne Farbe nachvollziehen, lange Hierarchien und Signaturen, leere Pfade, deaktivierte Ziele ohne Link.
- **72 Kontaktpräferenzen:** native Checkbox/Fieldset-Semantik, Tastatur, checked/disabled, Gruppenhint/-error, schmale Breiten, kein Formular und keine Datenübertragung in der Galerie.

**Ausstehend:** automatische CI-Prüfung, Zoom 200–400 %, Tastatur, Mobil- und Dunkelmodus, Screenreader. Keine Freigabe bis zur tatsächlichen Prüfung.

## K17 – Priorität C, Galerieabschnitte 73–78

- **73 Änderungsprotokoll:** semantisches `article`, `time` mit vorhandenem ISO-Zeitwert, ausgeschriebene Ereignisart, optionale Person, eingebettetes `change-review`; keine Speicherung, keine Audit-Fachfunktion.
- **74 Schlüssel/Wert-Editor:** Metadatenschlüssel, leere Werte, min/max, native Eingabefelder; ein Add-/Remove-Mechanismus mit Repeater.
- **75 Wiederholbare Gruppen:** dynamisches Hinzufügen/Entfernen, fehlende/besetzte Werte, Keyboard-Fokus, eindeutige `id`/indizierte Namen, mindestens/ höchstens N, 320px und 400%-Zoom. Servervalidierung separat.
- **76 Transferliste:** Strg-Mehrfachwahl, Move-Aktionen, Disabled-Einträge, sichtbarer Zähler, verborgene gewählte Feldwerte, Tastatur und schmale Bildschirme. Kein POST aus der Galerie.
- **77 Importvorschau:** Textstatus Bereit/Prüfen/Fehler/Übersprungen, Feldzuordnung als Tabelle, Quellzeilennummer, Kontrast und scrollbare Tabelle. Keine Importfunktion.
- **78 Stapelfortschritt:** abgeschlossene/laufende/fehlgeschlagene/offene Schritte, vorhandener `x-ui.progress` nur mit echtem Wert, kein erfundener Jobfortschritt; Screenreader und Reduced-Motion.

CI und manuelle Prüfung getrennt bewerten; bekannte CI-/Layoutprobleme gelten nicht durch K17 als behoben. Kein Baustein ist automatisch freigegeben.

## K18 – Priorität D, Galerieabschnitte 79–82

- **79 Skip-Links:** nur Ziele mit echten Ankern, sichtbare Fokusmarkierung mit Tab, Sprung zu vorhandenem Inhalt, keine verdeckten Fokusfallen; erst in späterer Seitenintegration direkt an Dokumentanfang einsetzen.
- **80 Back-to-Top:** funktionierende Anker mit und ohne URL, Fokus/Scrollposition und Bedienung bei 320–375px.
- **81 Tastaturhilfe:** `kbd`-Tasten lesbar, zugänglicher Gesamtname; nur tatsächlich existierende Shortcut-Aktionen bewerben.
- **82 Read-More:** Exzerpt bereits eingeklappt sichtbar, bei geöffnetem Volltext ohne Duplikat; native `details`, langen Inhalt und 400%-Zoom testen.

**Ausstehend für finale Freigabe:** automatisierte CI-Ergebnisse inklusive Accessibility, manuelle Tastatur-/Screenreader-/Darkmode-/Mobilprüfung; alter `OperationsCommandsTest` unabhängig klären. Kein Merge, keine Produktivseitenanpassung.
