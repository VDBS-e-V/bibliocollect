# Öffentliche Startseite V1 – Designlab-Review

**Status:** zur visuellen Abnahme vorgelegt, **keine** produktive Umstellung.

**Vorschau:** `http://localhost:8000/design-lab/startseite-v1`

**Altentwurf zum Vergleich:** `http://localhost:8000/design-lab/startseite`

## Konzept

Die abgenommene Komponentenbibliothek ist die verbindliche Basis. Die neue Startseite hat eine redaktionelle VDBS-Markenbühne mit funktionierender GET-Katalogsuche, vier unmittelbare Wege in die Anwendung, ein deutlich als Beispiel gekennzeichnetes Medienfeld, eine gemeinsame Bibliotheksgeschichte über `x-library.feature-overlap`, beide Dichten von `x-ui.week-schedule` und konkrete Service-Orientierung. Die bestehende App-Shell liefert Navigation und Footer; keine zweite globale Navigation oder neue Fachrouten.

**Wiederverwendung statt Kopie:** `x-library.catalog-search`, `x-library.quick-action`, `x-library.media-cover`, `x-library.media-card` in Comfortable und Compact, `x-library.feature-overlap` (Mint-Overlap mit VDBS-Figuren), `x-ui.week-schedule` (Comfortable/Compact), `x-library.notice-panel`, `x-ui.button`, `x-ui.back-to-top`.

## Bewusste Grenzen

- Nur die zusätzliche Route `public.design-lab.home-v1` unter `/design-lab/startseite-v1`; **ausschließlich** in `local` oder `testing` verfügbar.
- **Keine Änderung** an `resources/views/pages/welcome.blade.php`, `PublicHomeController`, Suche, Empfehlungsalgorithmus, Öffnungszeiten, Profilrechten oder den Produktivseiten.
- Keine als real ausgewiesenen Katalogdatensätze, Kennungen, Ausleihzustände oder Öffnungszeiten. Der Wochenplan verwendet die **aus der freigegebenen Komponenten-Galerie übernommenen, ausdrücklich als fiktiv gekennzeichneten Beispieluhrzeiten**; es sind **keine echten Schul-Öffnungszeiten**. Die gezeigten Medien sind fiktive Cover-/Textbeispiele **ohne** Medien-Detail-Links; für echte Bestände existiert ein klarer Katalog-Link.
- Die Suche ist der vorhandene `x-library.catalog-search`: native GET-Anfrage an den realen Katalog; alle anderen Links sind existierende, erlaubte Navigation. Keine POST-/PUT-/DELETE-Aktionen innerhalb des Entwurfs.
- Das Portalziel wird mittels bestehender `@can('surface.portal.access')`-Freigabe in Link zum Portal oder zur Anmeldung unterschieden. Die Sicherheitsregeln werden nicht verändert.
- Alle VDBS-Brandtokens und Schriftfamilien bleiben unverändert. Neue CSS-Regeln sind in `.bc-home-v1` gekapselt und nur über das vorhandene Designlab-Bundle eingebunden.

## Review: Desktop und Mobile

- [ ] **1280–1600 px:** Hero-Aufteilung und Mediencover, echte Katalogsuche ohne horizontalen Überlauf; Titel und Aktionen sind vollständig sichtbar.
- [ ] **1024 / 768 px:** Hero einspaltig, Cover-Komposition ohne verdeckte relevante Bedienelemente, Feature-Overlap lesbar.
- [ ] **375 / 320 px:** Suchfeld und Submit bleiben bedienbar; Quick-Action-Links sind untereinander; lange Beispielmedientitel brechen um; keine seitlichen Überläufe.
- [ ] **400%-Zoom:** keine abgeschnittenen Texte, keine horizontal verdeckten Aktionen, sichtbarer Fokus.
- [ ] **Dark/Light:** Kontrast von grünen VDBS-Akzenten, lila Hero, Button-/Linkzuständen sowie der Servicebox prüfen.
- [ ] **Tastatur:** Abschnitte-Sprunglinks, Suchfeld/GET-Submit, Schnellzugriffe, Galerie- und Footer-Links, Zurück-zum-Anfang.
- [ ] **Screenreader:** eine H1 im Seitenentwurf, korrekt benannte Abschnitte, Formlabel, visuell dekorative Cover im Hero `aria-hidden`, sinnvolle Linktexte.
- [ ] **Inhalt:** fiktive Uhrzeiten sind unmissverständlich als **Beispiel** markiert, nicht als reale Öffnungszeiten; keine real wirkenden Ausleihzusagen; frühere Studie bleibt unter alter Route erreichbar.
- [ ] **Gesamtabnahme:** die neue Seite durch Auftraggeber freigeben, erst danach eine ausdrücklich separate produktive Migration planen.

## Automatisierte Prüfung

`tests/Feature/DesignLabHomeV1Test.php`: Route und Texte, genau eine GET-Katalogsuche, Abschnitts-IDs, verwendete freigegebene Bausteine, keine Medien-Scheinlinks, unveränderte produktive Startseite und Altentwurf.

`scripts/support/site.mjs`: neue Designlab-V1 als Gastseite in vorhandene axe-core- und 320/375/768/1024-Pixel-Layoutprüfung aufgenommen.

Technische CI-Läufe können unabhängig vom bekannten fachfremden `OperationsCommandsTest.php:115` ausfallen. Das ist **keine** Freigabe durch den automatischen Test; der bestehende Draft-PR #92 bleibt ungemergt.

## Update: Feature Overlap und Wochenplan aus den Abnahmebeispielen

Die für die Startseite gewünschten Galerieelemente wurden im Entwurf V1 bewusst **als wiederverwendbare Komponenten** eingebunden:

- **Feature Overlap:** Der mintfarbene, rechts über der VDBS-Figurenillustration liegende Textblock verwendet `x-library.feature-overlap` mit der Leitidee **„Geschichten verbinden Menschen.“**; er ist in der mobilen Darstellung untereinander angeordnet.
- **Wochenplan (Comfortable):** sieben Wochentage mit den explizit übernommenen Demozeiten (Montag–Freitag) und geschlossenem Wochenende.
- **Wochenplan (Compact):** drei Sondertage mit Ferienhinweis, Schließtag und kürzeren Zeiten. So ist auch die zweite Dichte auf einer echten Seitenkomposition sichtbar.
- **Transparenz:** Ein deutlich sichtbarer Infohinweis über den Plänen kennzeichnet alle Uhrzeiten als **frei erfundene Demonstrationsdaten**. Es findet keinerlei automatische Kalenderauswertung oder produktive Datenanbindung statt.

Zusätzlicher Featuretest prüft zwei `bc-week-schedule`-Abschnitte, genau eine kompakte Variante, zehn Demo-Tage, den Mint-Overlap und die Fiktivkennzeichnung. Die CSS-Anpassung bleibt vollständig auf `.bc-home-v1` begrenzt.

## Nachfolgende Freigabe des realen lokalen Vorschauwegs

Der Auftraggeber hat den Arbeitsmodus geändert: Anstelle ausschließlich separater Designlab-Vorschauen soll die **echte Seite auf dem lokalen Feature-Branch** als direkt sichtbare Vorschau dienen, alternativ eine herunterladbare vollständige Vorschau. Darum ist der V1-Aufbau nun auch in `resources/views/pages/welcome.blade.php` integriert und direkt unter `http://localhost:8000/` sichtbar, sobald der Branch lokal aktualisiert wurde.

**Technische Sicherheitsgrenzen:** kein Hosting-Deployment, kein Merge, keine Änderung von `PublicHomeController`, `HomeShowcase`, Kalender-, Berechtigungs- oder Ausleihlogik. Die neue Blade-Ansicht zeigt weiterhin echte Katalogtitel und Statuswerte, echte Verwaltungskalender und vorhandene Leihbedingungen. Fiktive Demozeiten werden nicht in die echte Ansicht übernommen. Ohne reale Öffnungszeiten erscheint der vorhandene Hinweis zur fehlenden Hinterlegung; geschlossene Tage werden nicht erfunden. Die Designlab-V1 bleibt als Vergleich bestehen.

Neue scoped CSS-Datei: `resources/css/patterns/public-home-v1-live.css`, nach V1-Designlab-CSS importiert. Bestehender `PublicHomeRedesignTest.php` prüft weiterhin fünf Hauptabschnitte, Katalog-GET und ergänzt einen echten Wochenplan mit DB-Daten und den Mint-Overlap.
