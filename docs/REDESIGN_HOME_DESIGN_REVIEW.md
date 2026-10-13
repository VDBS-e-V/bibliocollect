# BiblioCollect – Visueller Review der Startseite

Stand: 11.10.2026 · Die Originalvorlage `index(2).html` / `style.css` bleibt der Ausgangspunkt. Die fünf Abschnitte werden **nicht** durch ein anderes Seitenkonzept ersetzt.

## Verglichene Referenzen und übertragbare Prinzipien

| Referenz | Beobachteter Schwerpunkt | Entscheidung für BiblioCollect |
|---|---|---|
| [About Google](https://about.google/intl/de_ALL/company-info/) | Eine klare Leitbotschaft, danach inhaltlich verschiedene redaktionelle Module | Die H1 ist die einzige sehr große Typografie, weitere Inhalte folgen mit abnehmender Hierarchie |
| [Apple](https://www.apple.com/) | Starke Einzelflächen, klare Hauptaktionen, wenige Elemente pro Bühne | Ein dominanter Hero mit Katalogsuche; keine zusätzlichen CTA-Kachelwände neben dem Hero |
| [SmartPixel](https://www.smartpixel.com/) | Klare inhaltliche Schwerpunkte und konkrete Produktszenarien | Reale Bibliotheksaktionen und Daten; keine funktionslosen Animations- oder 3D-Effekte |
| [Oodi Helsinki](https://oodihelsinki.fi/) | Bibliothek als Ort; Öffnungszeiten früh sichtbar; Nutzungsangebote im Vordergrund | Ein eigener redaktioneller Abschnitt für Lernen und Mitgestaltung; Direktlink zu Öffnungszeiten |
| [ZLB Berlin](https://www.zlb.de/) | Suche, Services und Öffnungszeiten werden klar ausgewiesen; redaktionelle Inhalte folgen | Katalogsuche und echte Schulzeiten als eigenständige, verständliche Abschnitte |
| [DNB](https://www.dnb.de/DE/Home/home_node.html) | Bibliografische Recherche, Katalog mit differenzierter Suche | Für die Suche übernehmen wir die bereits vorhandene BiblioCollect-Kataloglogik; die DNB-Homepage war bei der Recherche technisch nicht zuverlässig abrufbar |
| [Webdesign-Journal: Trends](https://www.webdesign-journal.de/webdesign-trends/) | Weniger Trend-Collagen, Minimalismus, Authentizität, gezielter Einsatz von Typografie und Microinteractions | Reduzierte UI-Effekte, wiederverwendbare Patterns und nur vorhandene VDBS-Markenbilder |

## Konkrete Umgestaltung

**01 – Hero:** derselbe VDBS-Lila-Grün-Kontrast, dieselbe dreizeilige Kernbotschaft und zwei illustrative Buchcover. Eine primäre Katalogsuche statt vieler konkurrierender Aktionen. Kurze Links zu erweiterter Suche und Öffnungszeiten.

**02 – Direkteinstiege:** vier konkrete Aufgaben, aber nicht mehr vier gleich große, gerahmte und beschattete Kacheln. Stattdessen klare Linkzeilen mit Piktogramm, kurzer Beschreibung und Pfeil. Desktop: zwei Spalten; Mobil: eine.

**03 – Medien entdecken:** ausschließlich echte Katalogdaten, echte Original- oder gespeicherte Fallback-Cover; klare Buchtypografie und knappe Verfügbarkeitsanzeige. Labels nur für Kategorien, die in den sichtbaren Medien vertreten sind.

**04 – Redaktioneller Abschnitt:** die frühere künstliche CSS-Regal-Illustration wurde durch die originale VDBS-Figurenkette (`/brand/vdbs/figures-chain.svg`) ersetzt. Keine fremden Stockfotos oder vermeintlich echten Schulbilder.

**05 – Öffnungszeiten und Ausleihe:** kein doppeltes Kartenraster; offene Informationsspalten mit feinen Trennlinien und verständlicher Schrift. Zahlen und Zeiten stammen weiterhin aus der Schulkonfiguration.

## Was bewusst unverändert bleibt

- VDBS-Farb-, Logo- und Typografie-Tokens;
- die zwei Navigationsebenen und der bestehende VDBS-Footer;
- reales Rollen- und Berechtigungssystem;
- Such- und Katalogrouten, Bestandsverfügbarkeit sowie SVG-Cover-Cache;
- exakt fünf Hauptbereiche als verbindliche Seitenstruktur.

## Prüfplan vor dem Merge

- `composer quality`, `npm run build`, SQLite-/MariaDB-Tests und GitHub-Barrierefreiheitscheck;
- visuell auf 320, 375, 768, 1280 und 1600 px, inklusive starkem Zoom und manuellem Dunkelmodus;
- Tastaturfokus, Suchformular, Screenreader-Überschriften, Hover-Kontraste und `prefers-reduced-motion`;
- echten Schulbestand mit weniger als zwei Medientiteln, mehreren Empfehlungen, keinen Öffnungszeiten, echten Originalcovern und generierten Coverdateien prüfen.

Ein Browser-Screenshot aus einem realen, befüllten Schulserver muss vor der finalen Freigabe folgen. Diese Review beschreibt die Änderungen, nicht bereits eine abgeschlossene visuelle Abnahme.
