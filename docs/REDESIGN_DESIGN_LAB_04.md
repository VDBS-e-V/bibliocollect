# Designstudio 04 – Musterseiten und Pattern-Bibliothek

## Lokal ansehen

Nur mit `APP_ENV=local` oder `APP_ENV=testing`:

- `/design-lab` – Übersicht mit visuellen Bento-Kacheln und direktem Masterplan-Einstieg
- `/design-lab/designsystem` – **grafischer Designsystem-Masterplan** mit Architektur, 15 Elementfamilien, 22 Templates, vier Anwendungsbereichen und acht Umsetzungsphasen
- `/design-lab/startseite` – Bibliotheksstartseite, Hero und Feature Overlap
- `/design-lab/medien` – Medien-Detailseite mit langen Covern und Verfügbarkeit
- `/design-lab/assistent` – horizontaler Prozess-Stepper mit fünf Schritten
- `/design-lab/dashboard` – Dashboard mit unterschiedlichen Bento-Modulgrößen und Meldungen
- `/design-lab/komponenten` – Pattern-Bibliothek mit Varianten und Anwendungsbeispielen

Die bisherige Gesamtübersicht `/design-review` verlinkt das neue Studio.

## Komponenten

| Blade-Komponente | Zweck | Verwendung |
|---|---|---|
| `x-library.feature-overlap` | visuelle Bühne mit überlappender Textfläche | Startseite, Themen und Medien |
| `x-library.process-stepper` | semantische Prozessachse (done/current/upcoming/error) | Import, Inventur, Schuljahreswechsel |
| `x-library.stat-tile` | flexible Statistikmodule für Bento-Raster | Portal, Betrieb, Verwaltung |
| `x-library.notice-panel` | klar strukturierte Meldungen mit Icon und Text | Formulare, Workflows, Dashboards |
| `x-library.media-cover` | echtes Cover oder typografisches Fallback | Katalog und redaktionelle Bereiche |

Die neue CSS-Bibliothek liegt in `resources/css/patterns/library-patterns-v2.css`.
Die fünf Musterseiten verwenden `resources/views/pages/design-lab.blade.php`
und `resources/css/patterns/design-lab.css`.

### Beispiel Process Stepper

```blade
<x-library.process-stepper
    label="Fortschritt des Medienimports"
    :steps="[
        ['label' => 'Datei wählen', 'status' => 'done'],
        ['label' => 'Daten prüfen', 'status' => 'current'],
        ['label' => 'Bestätigen', 'status' => 'upcoming'],
    ]"
    style="--bc-steps: 3;"
/>
```

Auf schmalen Bildschirmen wird die Prozessachse zu einer vertikalen Liste. Jede aktive Phase ist mit `aria-current="step"` gekennzeichnet; Icons und Texte vermitteln den Status unabhängig von Farbe.

### Beispiel Bento

```blade
<div class="bc-metric-bento">
    <x-library.stat-tile class="bc-stat-tile--wide" tone="purple"
        label="Aktive Ausleihen" :value="$activeLoans" />
    <x-library.stat-tile tone="mint"
        label="Offene Aufgaben" :value="$openTasks" />
</div>
```

Alle Zahlen müssen in realen Fachoberflächen aus der vorhandenen Domänenlogik kommen. Es gibt keine berechneten Trends oder frei erfundenen Prozentsätze.

### Beispiel Overlap

```blade
<x-library.feature-overlap
    eyebrow="Die Bibliothek entdecken"
    title="Ein Ort für deine Ideen"
    description="Lesen, forschen und miteinander lernen."
    tone="mint"
    :href="route('public.catalog.index')"
    action-label="Zum Katalog"
/>
```

Alternativ kann der `media`-Slot mit einem echten, freigegebenen Bibliotheksfoto oder einem lokalen Medien-Cover belegt werden. Ohne eigenes Bild wird die vorhandene VDBS-Figurenillustration angezeigt.

### Beispiel Notice

```blade
<x-library.notice-panel variant="warning" title="Prüfung erforderlich">
    Die eingegebenen Daten müssen noch überprüft werden.
</x-library.notice-panel>
```

## Designregeln

- VDBS-Farben und vorhandene semantische Tokens sind verbindlich.
- Das Overlap-Muster maximal einmal pro größerem Seitenabschnitt einsetzen.
- Bento nur dort, wo unterschiedlich gewichtete Informationen wirklich sinnvoll sind.
- Prozess-Stepper ausschließlich bei mehrstufigen Vorgängen.
- Wichtige Meldungen benötigen eine konkrete nächste Handlung, keine künstliche „Alles gut“-Deko.
- Originalcover haben Vorrang; lange generierte Cover-Titel umbrechen abhängig von ihrer Länge. Der persistente SVG-Cover-Cache ist weiterhin im CatalogCoverService.
- Die Musterseiten verwenden ausschließlich fiktive Daten, keine produktiven Operationen und keine neuen Berechtigungen.
- Produktionszugriff auf `/design-lab/*` ist deaktiviert.

## Vor Freigabe testen

- `composer quality` und `npm run build`;
- Tests `tests/Feature/DesignLabPageTest.php`;
- Desktop (1440px, 1280px), Tablet (768px), Mobil (390px, 320px);
- Vergrößerung 200% und 400%, Fokusreihenfolge, Kontraste und Dunkelmodus;
- lange deutschsprachige Titel, längere Prozessnamen, Bildschirmleser-Ansagen und Fehlerrollen;
- Daten- und Berechtigungsregeln bleiben in den ursprünglichen Fachseiten.

Die Seiten sind **Designbeispiele**. Sie übertragen noch nicht das neue Layout auf die produktiven Medienimport-, Inventur- oder Dashboardseiten.
