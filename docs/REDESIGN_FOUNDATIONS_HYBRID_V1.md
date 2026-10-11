# BiblioCollect Foundations – Hybrid v1.0 (Entwurf)

**Portalpriorität P-01:** [Portal-Template L08 – Ausleihen und Rückgabefristen zuerst](REDESIGN_PORTAL_TEMPLATE_PRIORITY.md). Die Richtung ist beschlossen; konkrete Fristwarnschwellen und Pixelwerte bleiben offen.

**Nächster Planungsabschnitt:** [Foundations-Workshop mit Designlab-Playground, Vergleichsszenen und Freigaben](REDESIGN_FOUNDATIONS_WORKSHOP_ROADMAP.md). Die Portal-Richtung „großzügiger Einstieg, kompakte Ausleih-/Medienlisten“ wurde im Workshopplan als Beschluss H-01 festgehalten; die Zahlenwerte bleiben Vorschläge.

> **Status: ZUR GESTALTERISCHEN FREIGABE.** Die hier festgelegten Größen, Abstände und Regeln sind konkrete Vorschläge. Der Entwurf ist **keine Änderung** an `resources/css/tokens.css` oder den produktiven Blade-Komponenten.
>
> **Designrichtung:** öffentlich großzügig und organischer; Bibliotheksbetrieb und Administration klar und kompakt; persönliches Portal mit großzügigem Einstieg und kompakten Ausleih-, Vormerkungs- und Medienlisten. Identische Marke, identische Fachlogik, gemeinsame semantische Tokenbasis.
>
> **Nicht verhandelbar:** Farben und Schriftfamilien aus `docs/BRANDING.md` / `resources/css/tokens.css` bleiben unverändert. Gruppierungsfarben werden nicht zu frei verwendbaren UI-Farben. Der Dunkelmodus bleibt manuell gesteuert.

## 0. Beobachteter Bestand vs. Neuvorschlag

Das Repository verwendet derzeit:
- feste Grundfarben (`--vdbs-green: #2DC08E`, `--vdbs-purple: #58275A`, `--vdbs-ink: #0F172A` etc.) sowie semantische Farbrollen;
- die Schriftfamilien `Lato` (UI), `Neuland` (kurze Marken-Headlines) und `Source Serif 4` (redaktionelle/formelle Akzente);
- ein Basis-Spacingsystem (`--space-1` bis `--space-16`), Radien (`--radius-sm:8px`, `md:12px`, `lg:16px`, `xl:24px`), Schatten und Höhen;
- einige komponentenspezifische Ausnahmen, z. B. Button-`border-radius:.3rem` außerhalb der zentralen Radien.

**Wichtig:** Die folgenden Werte sind **Zielwerte für die nächste Designrunde**, noch kein Ist-Zustand. Geänderte vorhandene Tokens müssen vor einem Rollout in mehreren Beispielseiten gegengetestet und möglichst durch neue semantische Aliase statt durch harte globale Überschreibungen eingeführt werden.

## 1. Prinzipien

1. **Ein Produkt, zwei Dichten:** `comfortable` für öffentlich und weite Portalansichten; `compact` für Listen, Formulare und Scanner-Workbenches. Der Nutzer muss sich nicht zwischen zwei visuellen Marken umstellen.
2. **Lesbarkeit vor Dekoration:** Bodytext mindestens 16px; verdichtete Datenlabels in speziellen Arbeitsansichten 14px; 12px nur für nichtkritische Metadaten.
3. **Struktur vor Rahmen:** Abstand und Hierarchie zuerst, Linien als zweite Ebene, Schatten nur bei wirklicher Erhebung.
4. **Spannung durch Komposition:** höchstens eine dominante Overlap-Bühne pro Abschnitt; Bento nur für unterschiedlich wichtige Inhalte, nicht für normale Formulare.
5. **Echte Daten statt dekorativer Signale:** Kennzahlen, Fristen, Status und Fortschritte ausschließlich aus realer Fachlogik.
6. **Vorhersehbare Bedienung:** Navigationsaktionen, Formularaktionen, Scannerfokus und Fehlerbehandlung verhalten sich überall konsistent.
7. **Barrierefreiheit als Systemqualität:** Kontrast, Reflow, Tastatur, Touchziele, Textvergrößerung und Fehlerbezeichnungen schon beim Design prüfen.

## 2. Typografie – verbindliche Soll-Skala

Es bleiben `Lato`, `Neuland`, `Source Serif 4`. Eine textliche Rolle entscheidet die Schrift. `1rem = 16px` bei der üblichen Browsereinstellung; der Nutzer kann die Standardschriftgröße ändern.

| Rolle | Desktop | Mobil | Zeilenhöhe | Gewicht | Font | Verwendung |
|---|---:|---:|---:|---:|---|---|
| `display-xl` | 72px (4.5rem) | 42px (2.625rem) | 1.05 | 900 | Lato; Neuland nur kurze Branding-Akzente | Ausnahme-Hero |
| `display-lg` | 56px (3.5rem) | 38px (2.375rem) | 1.1 | 900 | Lato | öffentliche Bühne |
| `heading-h1` | 44px (2.75rem) | 32px (2rem) | 1.15 | 900/700 | Lato | Seitentitel |
| `heading-h2` | 32px (2rem) | 26px (1.625rem) | 1.2 | 700 | Lato | Abschnitt |
| `heading-h3` | 24px (1.5rem) | 22px (1.375rem) | 1.25 | 700 | Lato | Unterabschnitt |
| `heading-h4` | 20px (1.25rem) | 20px (1.25rem) | 1.3 | 700 | Lato | Karten, Paneltitel |
| `body-lg` | 18px (1.125rem) | 18px | 1.6 | 400 | Lato | Einführung, Editorial |
| `body` | 16px (1rem) | 16px | 1.55 | 400 | Lato | Standardtexte |
| `body-strong` | 16px | 16px | 1.55 | 700 | Lato | Hervorhebung |
| `ui-label` | 16px | 16px | 1.4 | 700 | Lato | Formulare |
| `ui-dense` | 14px (.875rem) | 14px | 1.45 | 400/700 | Lato | Tabellendaten und kompakte Hilfen |
| `meta` | 14px (.875rem) | 14px | 1.45 | 400 | Lato | Metadaten |
| `micro` | 12px (.75rem) | 12px | 1.45 | 700 | Lato | ergänzende, nicht wesentliche Angaben |
| `editorial` | 18–20px | 18px | 1.55 | 400 | Source Serif 4 | kurze Zitate/Featuretexte |
| `stat` | 40–56px (fluid) | 32–40px | 1.0 | 900 | Lato | KPI-Zahlen |

**Zwei Dichteprofile, drei Nutzungskontexte** (das Portal kombiniert `comfortable` und `compact`, ohne eine eigene Tokenfamilie):
- Public: `display-lg` oder `display-xl` für maximal einen Haupt-Hero; normale h1-h4-Skala darunter.
- Workbench: H1 maximal 32px Desktop / 28px Mobil, H2 24px, H3 20px; regulärer Body bleibt 16px; `ui-dense` nur für echte Arbeitsdaten.
- Portal: Begrüßung und Orientierung `comfortable`; Ausleihen, Vormerkungen und Merkliste gezielt `compact`; normale Texte und Formularlabels bleiben 16px. Die Portal-H1 von 36px Desktop ist **nur ein Vorschlag**.
- **Keine festen Zeilenanzahlen für Überschriften.** Lange Buchtitel, Namen und zusammengesetzte Wörter dürfen umbrechen; keine allgemeine Ellipse für wesentliche Informationen.
- Fließtext maximal `68ch` (Standard), Editorial ca. `58ch`; lange Formularanleitungen kürzer. Keine erzwungenen Zeilenumbrüche bei 320px.
- WCAG 2.2 Text Spacing: Überschreibungen durch Nutzende müssen ohne Verlust funktionieren (nicht zwingend die Standardwerte sein).

## 3. Spacing – 4px-Raster mit kleinen Zwischenstufen

| Token | Sollwert | Hauptverwendung |
|---|---:|---|
| `space-0` | 0px | keine Lücke |
| `space-0.5` | 2px | nur optische Mikrokorrektur |
| `space-1` | 4px | Icon/Label fein |
| `space-2` | 8px | eng verwandte Inhalte |
| `space-3` | 12px | Label/Hilfetext |
| `space-4` | 16px | Formulargruppe, kompakte Karten |
| `space-5` | 20px | zwischen kleinen Modulen |
| `space-6` | 24px | Kartenpadding, Grid-Gap |
| `space-8` | 32px | größere Komponenten |
| `space-10` | 40px | kompakte Seitenabschnitte |
| `space-12` | 48px | Abschnittswechsel intern |
| `space-16` | 64px | mittlerer Public-Weißraum |
| `space-20` | 80px | großzügiger Public-Abstand |
| `space-24` | 96px | Public-Sektionsabstand Desktop |
| `space-32` | 128px | große redaktionelle Bühnen, selten |

**Semantische Abstände je Profil**:
| Zweck | Comfortable/Public | Compact/Workbench |
|---|---:|---:|
| Formular-Gap | 20px | 12px |
| Komponenten-Gap | 24px | 16px |
| Kartenpadding | 24–32px | 16–20px |
| Grid-Gap | 24px | 12–16px |
| Sektionsabstand Desktop | 80–96px | 32–48px |
| Sektionsabstand Mobil | 48–56px | 24–32px |
| Seiteninnenabstand Mobil | 16px | 16px |
| Abstand Heading zu Inhalt | 24–32px | 16–24px |

**Regel:** vertikalen Rhythmus über `gap` und zentrale Layout-Komponenten erzeugen; keine zufälligen Einzel-`margin`-Werte als neues Layoutsystem.

## 4. Größen und Dichte

| Element | Comfortable | Compact | Hinweise |
|---|---:|---:|---|
| Primary/Secondary Button | 48px Höhe | 44px Höhe | mobil immer mindestens 44px |
| Textfeld/Select/Autocomplete | 48px | 44px | mobil nach Möglichkeit 48px, Input-Schrift 16px |
| Icon-Button | 44 × 44px | 44 × 44px | Icon ist kleiner, Trefferfläche bleibt groß |
| Scanner-/Barcodeeingabe | 56px | 56px | Fokus, Rückmeldung und ausreichend große Zeichen |
| Tabellenzeile | mindestens 52px | mindestens 44px | bei mehrzeiligem Inhalt wächst Höhe |
| Navigationseintrag | mindestens 44px | mindestens 44px | auch mobil |
| Badge | dynamisch, ca. 26–30px | dynamisch, ca. 24–28px | nicht zwingend klickbar |
| Dialog-Aktionsbutton | mindestens 44px | mindestens 44px | eindeutige Fokusreihenfolge |
| Stepper-Kreis | 40px | 36px | Status über Zahl/Check und Text |
| Klick-/Touchziel | Ziel 44×44px | Ziel 44×44px | Textlinks können inhärente Ausnahmen haben |

WCAG 2.2 Target Size (Minimum) nennt **24 × 24 CSS-Pixel** mit Ausnahmen (AA). Unsere 44px sind eine bewusst großzügigere Produktregel, **kein erfundener WCAG-AA-Mindestwert**.

## 5. Rundungen und Geometrie

Grundradien als stabile Skala (bestehende Größen zunächst mappen, nicht global überschreiben):

| Token | Sollwert | Beispiel |
|---|---:|---|
| `radius-none` | 0px | Tabellenlinien, Tags bei Bedarf |
| `radius-xs` | 4px | sehr kleine Labels, Marker |
| `radius-sm` | 8px | Inputs, Buttons, kompakte Karten |
| `radius-md` | 12px | Standardkarten, Meldungen |
| `radius-lg` | 20px | Public-Karten und Bento-Panels |
| `radius-xl` | 28px | größere öffentliche Bühnen |
| `radius-pill` | 999px | Chips, Badges, Schrittkreise |

Kontextzuordnung:
- Buttons und Formulare: **8px in beiden Profilen** (gleiche Interaktionssprache).
- Arbeitstabellen und Panel: **0–8px**; Rahmen/Trennlinie vor Schatten.
- Public Media-/Contentkarte: **12–20px**; große Bühne maximal 28px.
- Overlap-Panel: **8–12px**, damit kantige Fotoüberlagerungen lebendig, aber nicht übertrieben wirken.
- Kleines Badge: Pill nur, wenn lesbar und sinnvoll.

## 6. Linien, Ebenen und Schatten

| Rolle | Vorschlag | Anwendungsfall |
|---|---|---|
| `border-subtle` | 1px, bestehende `--border-default` | Reihen und sanfte Trennung |
| `border-default` | 1px, bestehende Token | Inputs, einfache Panels |
| `border-strong` | 1–2px, bestehender Farbtoken | gezielte Abgrenzung |
| `focus-outline` | 3px + 3px Abstand, `--focus-ring` | :focus-visible, nicht durch Schatten ersetzen |
| `shadow-none` | none | Tabellen, Arbeitslisten, Textfelder |
| `shadow-sm` | 0 1px 2px rgb(15 23 42 / 6%) | leicht erhobene Einzelelemente |
| `shadow-md` | 0 6px 20px rgb(15 23 42 / 9%) | hervorgehobene Public-Card/Overlap |
| `shadow-overlay` | 0 18px 48px rgb(15 23 42 / 18%) | Dialoge, Popovers, Menüs |

Die Hex-Farben bleiben im Marken-/Semantiksystem. Ob eine Rolle im Dunkelmodus andere Transparenzen benötigt, wird durch konkrete Kontrast- und Sichtprüfung entschieden.

## 7. Contentbreiten & responsive Grids

| Container | Breite | Nutzung |
|---|---:|---|
| `container-reading` | max. 720px (45rem) | Hilfen, Artikel, längere Formulare |
| `container-form` | max. 800px (50rem) | Bearbeiten, Konto, Verwaltungsdialog |
| `container-standard` | max. 1200px (75rem) | Public-Katalog, Portal, allgemeine Seiten |
| `container-wide` | max. 1440px (90rem) | Dashboards, breite Tabellen, Scanner |
| `container-full` | 100% | breite Bühne/Hero mit innenliegendem Standardcontainer |

Außenabstand: Mobil 16px, Tablet 24–32px, Desktop 32–48px; kein horizontaler Seiten-Scroll bei 320 CSS-Pixeln. Reflow vor starrer Spaltenzahl.

Breakpoint-Kandidaten (content-first): **480px, 768px, 1024px, 1440px**; diese Grenzen müssen bei echten Inhalte-/Zoom-Tests bestätigt werden.
- 1440+: Public bis 12-spaltiges Raster; große Overlap-Bühne.
- 768–1439: 6–12 nutzbare Spalten je tatsächlicher Breite; Bento 2–3 Spalten.
- unter 768: überwiegend 1–2 Spalten, Bild/Text wird vertikal, Filter als bedienbare mobile Variante.
- bei 320px/400%: eine Inhaltsspalte, kein Textverlust; ausgenommen spezielle zweidimensionale Datentabellen, die separat scrollbar und verständlich bedienbar sind.

**Layout-Regel:** Visuelle Bento-Umsortierung darf nicht die logische Lese- und Tastaturreihenfolge verfälschen (kein `grid-auto-flow:dense` für interaktive Elemente).

## 8. Flächensystem & Farbanwendung (Farben unverändert)

Bestehende Farbrollen weiterverwenden:
`--bg-page`, `--bg-surface`, `--bg-surface-muted`, `--bg-brand-soft`, `--bg-stage`, `--text-primary`, `--text-secondary`, `--border-default`, `--focus-ring`.

**Kontrast beachten:**
- VDBS-Grün `#2DC08E` gegen Weiß ca. **2.32:1**: Grün ist kein normaler kleiner Text auf Weiß.
- `#0F172A` auf VDBS-Grün ca. **7.69:1**: für Hauptbutton-Text gut geeignet.
- `#58275A` auf Weiß ca. **11.33:1**: für große Markenflächen und Text gut geeignet.
- Für grüne Links auf Weiß bestehendes dunkles `--link-default` nutzen statt rohes Marken-Grün.
- Interaktionszustände niemals ausschließlich durch Farbunterschied darstellen.

**Flächenpriorität:**
1. Page: primärer Hintergrund;
2. Surface: Form/Tabellen/Karten;
3. Muted/Brand-soft: Themenabschnitt, Information und Besonderheit;
4. Stage: großer Public-Hero;
5. Overlay: Popover/Modal mit getrenntem Z-Layer;
6. Statusflächen: nur aus bestehenden semantischen Rollen und nach Kontrastprüfung.

Dark Mode bleibt bewusst über `[data-vdbs-theme=dark]` gesteuert; `prefers-color-scheme` nicht als automatische Wahl.

## 9. Overlap, Bento, Stepper, Cover

**Feature Overlap**
- Desktop: Bild-/Medienfläche etwa 55%, Textfläche 45%, Überschneidung ca. 48–80px.
- Mobil: vertikal, Textpanel maximal 24px über dem unteren Bildrand.
- Kein verdeckter Text, kein überlappender Fokusrahmen; echte Bildquelle mit Alt-Text und Bildrechten, sonst VDBS-Illustration.
- Öffentlich betont; im Betrieb nur, wenn ein echter Informationszweck besteht.

**Bento**
- 12px/16px Gaps Workbench, 24px Public; nur 1–2 größere Leitelemente pro Grid.
- Semantische Reihenfolge entspricht visueller Reihenfolge; keine künstlichen Trendwerte.
- Weniger Rundungen/Schatten intern; farbige Stat-Tiles nur für klar begründete Hierarchie.

**Process Stepper – vereinbarte neue Geometrie**
- Kreis (36/40px) **und Titel rechts daneben auf derselben horizontalen Achse**.
- Verbindungssegment liegt hinter der Inline-Gruppe, wird durch deren Flächenhintergrund *zwischen* Titel und folgendem Kreis sichtbar; kein Text auf der Linie.
- Status über Check/Zahl/Fehlericon **und** lesbare Beschriftung; `aria-current="step"` für aktuellen Schritt.
- Bei engen Breiten vertikale Liste (Kreis links, Titel rechts, Verbindung nach unten), kein abgeschnittener Titel; kurze Unterzeile optional.
- Stepper ist Fortschrittsinformation, nicht automatisch klickbare Navigation.

**MediaCover**
- Originalcover vor generiertem Ersatzcover; persistierter SVG-Cache bleibt unverändert.
- Lange Titel umbrechen innerhalb der Coverfläche; Autor:in bleibt lesbar, keine pauschale 1-Zeilen-Trunkierung.
- Die vier vorhandenen Farbvarianten bleiben unverändert; verifizierte Kontraste und vordefinierte Schriftgrößen nach Coverbreite.
- Sehr lange Titel brauchen ggf. alternative Typografie, aber keine unabhängig generierten Bildvarianten pro Seitenaufruf.

## 10. Motion und Iconografie

| Token/Regel | Ziel |
|---|---|
| `duration-fast` | 120ms; Hover/Fokus |
| `duration-normal` | 180ms; kleine Komponentenübergänge |
| `duration-slow` | 260ms; Panels und Overlap, selten |
| `easing-standard` | `ease-out` |
| `motion-reduced` | 0ms für nicht notwendige Effekte |
| `icon-sm` | 16px; dichte Tabellen |
| `icon-md` | 20px; Standardbuttons und Meldungen |
| `icon-lg` | 24px; Hero/Leitkarten |
| `icon-stroke` | 1.75–2px visuelle Linie |

Keine dauernden Parallax-, Floating- oder rein dekorativen Animationen. Icons unterstützen, ersetzen aber keine wichtigen Labels.

## 11. Qualitäts- und Rollout-Kriterien

Vor Freigabe im Designlab mit echten Beispielzuständen prüfen:
- Public Hero, MediaGrid, Form, DataTable, ProcessStepper, Bento, Notice, Scanner und MediaCover;
- 320px, 375px, 768px, 1280px, 1440px sowie 200%/400%-Zoom;
- Tastatur, sichtbarer Fokus, Screenreader, Kontrast, Browser-Autofill, Dark Mode;
- langes Deutsch, sehr lange Buchtitel, fehlende Autor:innen, leere Listen, Ladezustände;
- `prefers-reduced-motion`, Druck und langsame Verbindungen;
- Zielwerte nach WCAG 2.2 AA; wichtige Ausnahmen bei Daten-Workbenches begründen und testen.

Die **umfassende CSS-Migration** erfolgt erst nach visueller Freigabe. Pro Surface eine Pilotseite:
1. Public: Katalog/Detail;
2. Portal: Meine Ausleihen;
3. Betrieb: Scanner/Arbeitsliste;
4. Administration: Einstellungsformular.

## 12. CSS-Ziel-API (nur Beispiel, noch NICHT eingespielt)

```css
/* Vorschlag, ersetzt keine bestehenden Farb- und Fontdefinitionen */
:root {
    --ds-radius-xs: .25rem;
    --ds-radius-sm: .5rem;
    --ds-radius-md: .75rem;
    --ds-radius-lg: 1.25rem;
    --ds-radius-xl: 1.75rem;

    --ds-content-reading: 45rem;
    --ds-content-form: 50rem;
    --ds-content-standard: 75rem;
    --ds-content-wide: 90rem;

    --ds-control-comfortable: 3rem;
    --ds-control-compact: 2.75rem;
    --ds-control-scan: 3.5rem;

    --ds-space-section-public: clamp(3rem, 6vw, 6rem);
    --ds-space-section-workbench: clamp(1.5rem, 3vw, 3rem);

    --ds-motion-fast: 120ms;
    --ds-motion-normal: 180ms;
    --ds-motion-slow: 260ms;
}
.bc-surface--public, .bc-surface--portal {
    --component-gap: 1.5rem;
    --component-card-padding: 1.5rem;
    --component-radius: var(--ds-radius-lg);
    --component-control-height: var(--ds-control-comfortable);
}
.bc-surface--pos, .bc-surface--administration {
    --component-gap: 1rem;
    --component-card-padding: 1rem;
    --component-radius: var(--ds-radius-sm);
    --component-control-height: var(--ds-control-compact);
}
```

**Nächste Freigabeentscheidung:** Schriftgrößen/Zeilenhöhen, Radien, Dichte und Containerbreiten an vier Musterseiten *nebeneinander* vergleichen. Nach Zustimmung die Tokens zunächst als additive Aliase im Designlab realisieren, dann gezielt in die bestehenden Komponenten migrieren.

## Verifizierbare Referenzen

- `docs/BRANDING.md` – verbindliche Marke, Farben, Schriftfamilien, Theme.
- `resources/css/tokens.css` – aktuelle Tokens/semantische Farben.
- `docs/DESIGN_SYSTEM.md` – Architektur Token → Component → Pattern → Template → Page.
- `docs/REDESIGN_DESIGN_SYSTEM_MASTERPLAN.md` – 15 Familien, 22 Grundtemplates, 67 Seitenszenarien.
- [W3C WCAG 2.2 Quick Reference](https://www.w3.org/WAI/WCAG22/quickref/)
- [W3C Text Spacing 1.4.12](https://www.w3.org/WAI/WCAG22/Understanding/text-spacing)
