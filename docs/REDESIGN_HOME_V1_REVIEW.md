# Öffentliche Startseite V1 – Designlab-Review

**Status:** zur visuellen Abnahme vorgelegt, **keine** produktive Umstellung.

**Vorschau:** `http://localhost:8000/design-lab/startseite-v1`

**Altentwurf zum Vergleich:** `http://localhost:8000/design-lab/startseite`

## Konzept

Die abgenommene Komponentenbibliothek ist die verbindliche Basis. Die neue Startseite hat eine redaktionelle VDBS-Markenbühne mit funktionierender GET-Katalogsuche, vier unmittelbare Wege in die Anwendung, ein deutlich als Beispiel gekennzeichnetes Medienfeld, eine gemeinsame Bibliotheksgeschichte über `x-library.feature-overlap` und konkrete Service-Orientierung. Die bestehende App-Shell liefert Navigation und Footer; keine zweite globale Navigation oder neue Fachrouten.

**Wiederverwendung statt Kopie:** `x-library.catalog-search`, `x-library.quick-action`, `x-library.media-cover`, `x-library.media-card` in Comfortable und Compact, `x-library.feature-overlap`, `x-library.notice-panel`, `x-ui.button`, `x-ui.back-to-top`.

## Bewusste Grenzen

- Nur die zusätzliche Route `public.design-lab.home-v1` unter `/design-lab/startseite-v1`; **ausschließlich** in `local` oder `testing` verfügbar.
- **Keine Änderung** an `resources/views/pages/welcome.blade.php`, `PublicHomeController`, Suche, Empfehlungsalgorithmus, Öffnungszeiten, Profilrechten oder den Produktivseiten.
- Keine erfundenen Katalogdatensätze, Kennungen, Ausleihzustände oder Öffnungszeiten. Die gezeigten Medien sind fiktive Cover-/Textbeispiele **ohne** Medien-Detail-Links; für echte Bestände existiert ein klarer Katalog-Link.
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
- [ ] **Inhalt:** keine fiktiven Öffnungszeiten oder real wirkenden Ausleihzusagen; frühere Studie bleibt unter alter Route erreichbar.
- [ ] **Gesamtabnahme:** die neue Seite durch Auftraggeber freigeben, erst danach eine ausdrücklich separate produktive Migration planen.

## Automatisierte Prüfung

`tests/Feature/DesignLabHomeV1Test.php`: Route und Texte, genau eine GET-Katalogsuche, Abschnitts-IDs, verwendete freigegebene Bausteine, keine Medien-Scheinlinks, unveränderte produktive Startseite und Altentwurf.

`scripts/support/site.mjs`: neue Designlab-V1 als Gastseite in vorhandene axe-core- und 320/375/768/1024-Pixel-Layoutprüfung aufgenommen.

Technische CI-Läufe können unabhängig vom bekannten fachfremden `OperationsCommandsTest.php:115` ausfallen. Das ist **keine** Freigabe durch den automatischen Test; der bestehende Draft-PR #92 bleibt ungemergt.
