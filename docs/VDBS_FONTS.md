# VDBS-Webfonts in BiblioCollect

BiblioCollect lädt keine Schriftdateien von externen Font-CDNs. Die benötigten Schriftdateien sind als Vite-Quellassets unter `resources/fonts/vdbs` versioniert. `resources/css/foundation/font-faces.css` referenziert sie relativ, sodass Vite sie beim Produktionsbuild mit gehashten Dateinamen nach `public/build/assets` übernimmt.

Ein separates Laufzeitverzeichnis `public/fonts/vdbs` ist deshalb nicht erforderlich. Auch lokale Font-ZIP-Archive gehören nach der abgeschlossenen Migration nicht mehr zum Projekt und werden für Build oder Laufzeit nicht benötigt.

## Eingebundene Schnitte

- Lato: Regular 400, Italic 400, Bold 700, Black 900
- Source Serif 4: Regular 400, Italic 400, SemiBold 600
- NeulandFont 2017: Regular 400

## Typografische Rollen

Lato ist über `--font-family-base` die Standardschrift der Anwendung und wird auch als Tailwind-`font-sans` bereitgestellt. Normale UI, Fließtext, Tabellen, Formulare und Überschriften bleiben damit bei Lato.

Source Serif 4 ist ausschließlich über die redaktionelle Rolle `--font-family-editorial` / `.vdbs-editorial` vorgesehen, etwa für formellere Inhalte oder Zitate. Sie ist keine allgemeine UI-Schrift.

Neuland ist ausschließlich über `--font-family-display` / `.vdbs-display` vorgesehen. Die Display-Klasse setzt `--text-4xl` (2,25 rem, bei 16 px Root-Schriftgröße 36 px) und ist nur für wenige Wörter in plakativem Markenkontext gedacht, niemals für Fließtext oder normale Bedienelemente.

## Build und Prüfung

`npm run build` muss alle acht TTF-Dateien als gehashte Assets unter `public/build/assets` ausgeben. Die Architekturtests prüfen, dass die Quellassets und Lizenzhinweise vorhanden sind, keine alten `/fonts/vdbs/...`-Runtime-URLs verwendet werden und keine alten öffentlichen TTFs oder Font-ZIP-Archive im Projekt verbleiben.

Die Lizenzhinweise liegen gemeinsam mit den Font-Quellassets unter `resources/fonts/vdbs/licenses`.
