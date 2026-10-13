# BiblioCollect · Portaltemplate L08 · Priorität P-01

> **Beschlossen:** Ausleihen und Rückgabefristen sind die wichtigste Information des persönlichen Portals.
>
> **Beschlossen (H-01):** Das Portal nutzt einen großzügigen Einstieg, danach kompakte Medien-, Ausleih- und Vormerkungslisten. Farben und Schriftfamilien bleiben bestehen.
>
> **Status:** Informationsarchitektur freigegeben; konkrete Pixelwerte und visuelle Gestaltung sind im Review. Produktives Portal und Berechtigungen wurden noch nicht migriert.
>
> **Designlab:** `/design-lab/portal` (nur `local` / `testing`).

## 1. Beobachteter Fachbestand

Die derzeitige Portalansicht `resources/views/pages/surfaces/portal.blade.php` besitzt unter anderem:
- `$openLoans` mit Rückgabetermin `due_on`, `renewal_count` und Verlängerungsblockaden `$renewalBlocks`;
- überfällige Medien werden aus `due_on` gegenüber dem aktuellen Datum ermittelt;
- `$openReservations` mit wartendem oder abholbereitem Status und ggf. `pickup_until`;
- Konto-Verknüpfung (`$patron === null`), Erinnerungen und Datenschutz;
- die vorhandenen POST-Aktionen für Verlängerung, Stornierung und Einstellungen, die beim Redesign erhalten bleiben müssen.

Das Design fügt **keine neue Fachregel** für Verlängerung, Fristen, Mahnungen oder Vormerkungen hinzu.

## 2. Die verbindliche visuelle Informationshierarchie

| Reihenfolge | Abschnitt | Priorität und Zweck |
|---:|---|---|
| 1 | Begrüßung und Orientierung | Kurz, freundlich, keine riesige Marketing-Bühne; folgende Fristen bereits im ersten sichtbaren Bereich |
| 2 | **Nächste Rückgaben / dringende Aufgaben** | Überfällige Medien und zeitnahe Fälligkeiten **vor** Statistik, Empfehlungen und weiteren Angeboten |
| 3 | **Aktive Ausleihen** | Kompakte vollständige Liste mit Titel, Datum, Status und nur erlaubten Aktionen |
| 4 | Vormerkungen | Abholbereite Medien mit Abholfrist vor wartenden Vormerkungen |
| 5 | Weiterführende Kontofunktionen | Merkliste, Leselisten, Buchwünsche, Datenschutz und Einstellungen |
| 6 | Medien entdecken | Nur nachgeordnet, kein heroartiger Empfehlungsschwerpunkt oberhalb der Ausleihen |

**Wichtig:** Eine *konkret bald endende Abholfrist* kann dringender sein als eine weit in der Zukunft liegende Rückgabe. Die endgültige Priorisierung gleicher Dringlichkeitsstufen muss aus den tatsächlich vorhandenen Daten, Regeln und Fachanforderungen abgeleitet werden – nicht aus erfundenen „3-Tage“-Grenzen.

## 3. Fristen- und Statuslogik der Darstellung

- Überfälligkeit: eindeutiges Textlabel „Überfällig“ plus Hinweis auf Rückgabe; nicht nur Rot.
- Fällig heute / zeitnah: deutlich sichtbares Fälligkeitsdatum; „zeitnah“ ist erst nach abgestimmtem Schwellenwert eine konkrete Klassifikation.
- Normale Ausleihe: kein Alarm- oder Erfolgssignal ohne Handlungsbedarf.
- Verlängerung: nur als echtes Formular zeigen, wenn bestehende Berechtigungs- und Fachlogik sie erlaubt; sonst verständlichen Blockgrund darstellen.
- Abholbereite Vormerkung: Abholstatus und verfügbares `pickup_until` hervorheben; wartende Vormerkungen nachrangig.
- Konto nicht verknüpft: verständlicher erster Schritt anstelle irreführender Ausleihzahlen.
- Keine Ausleihen: Empty State mit Link zum Katalog und ohne künstliche Fristen.
- Fehlende Fälligkeits-/Abholdaten: keine fiktiven Termine, sondern fachlich abgestimmtes neutrales Label.
- Keine empfindlichen Kontodaten aus dem lokalen Designlab in produktive Räume übertragen.
- Relative Angaben („in 3 Tagen“) sind im Designlab nur Demonstrationswerte; in echten Seiten Fälligkeit mit eindeutigem Datum und bei Bedarf korrekt berechneter relativer Angabe ausgeben.

## 4. Layout und Foundations

**Comfortable als Portal-Rahmen:** großzügiger, aber kurzer Seiteneinstieg, Titelgröße als Kandidat 36px Desktop, Abschnittsgap ca. 48–64px.

**Compact in Listen:** reduzierte Zeilenabstände, Tabellen- oder Listenhöhe mindestens 44px wo bedienbar, stets ausreichend Touchziele, Mehrzeilentitel und sichtbare Interaktionszustände.

**Semantische Komponenten:** gemeinsame `MediaCover`-, `Badge`-, `NoticePanel`-, `Button`- und List-/Tabellen-Komponenten. Keine duplizierte Portal-Farb- oder Schriftwelt.

**Responsive:** mobile Einspaltigkeit; wichtige Fristen **vor** Ausleihliste; Titel darf umbrechen, Status und Rückgabe bleiben sichtbar; keine horizontale Vollseiten-Scrollfläche. Untergeordnete Links folgen nach den dringenden Informationen.

## 5. Designlab-Prüfvarianten

1. Mindestens eine Ausleihe ist überfällig.
2. Mehrere Ausleihen mit unterschiedlichen Rückgabefristen.
3. Sehr lange Buchtitel in kompakten Listen.
4. Eine Vormerkung ist abholbereit (mit vorhandener Abholfrist).
5. Verlängerung ist gesperrt.
6. Keine Ausleihen und/oder keine Vormerkungen.
7. Konto nicht verknüpft.
8. Tablet, Smartphone, 320px, 200%/400%-Zoom, manueller Dunkelmodus, Tastatur.

Das neue `/design-lab/portal` illustriert die Zustände mit **statischen Demo-Inhalten**; das spätere reale Layout braucht eine fachliche Priorisierungsfunktion für echte Termine und Berechtigungen, sofern die vorhandene Portalansicht diese nicht bereits liefert.

## 6. Technische Umsetzung nach visueller Freigabe

1. Das vorhandene `PortalHomeController`-Datenmodell überprüfen; nur Daten verwenden, die existieren und für die Nutzerin/den Nutzer freigegeben sind.
2. Eine knappe Prioritätszusammenfassung für offene Ausleihen und abholbereite Vormerkungen aus den echten Datumsfeldern ermitteln.
3. Gestaltung in wiederverwendbaren Komponenten realisieren; Rollen- und Ausleihlogik bleibt unverändert.
4. Bestehende Formularrouten `portal.loans.renew` und `portal.reservations.cancel` inklusive CSRF/Policies beibehalten.
5. Fachtests (leeres Konto, überfällig, gesperrte Verlängerung, ready/queued), responsive Sichtprüfung und Accessibility-Checks.
6. Erst dann die produktive Portal-Ansicht sukzessive auf die neuen Styles migrieren.

**Nicht beschlossen:** konkrete Warnfristen, relative Tagesangaben in produktiven Daten, endgültiger H1-Wert, Portal-Kartenradius und individuell konfigurierbare Dichte. Diese Fragen sind Teil der noch offenen Foundations-Abnahme.
