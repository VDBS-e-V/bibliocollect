# VDBS-Webfonts in BiblioCollect

BiblioCollect lädt keine Schriftdateien von externen Font-CDNs. Die benötigten Schriftdateien liegen als Vite-Quellassets unter `resources/fonts/vdbs`. `resources/css/foundation/font-faces.css` referenziert sie relativ, sodass Vite sie beim Produktionsbuild mit gehashten Dateinamen nach `public/build/assets` übernimmt.

Damit verschwinden auch die bisherigen Vite-Hinweise, dass `/fonts/vdbs/...` erst zur Laufzeit aufgelöst werden müsse.

## Eingebundene Schnitte

- Lato: Regular 400, Italic 400, Bold 700, Black 900
- Source Serif 4: Regular 400, Italic 400, SemiBold 600
- NeulandFont 2017: Regular 400

Lato ist die Standardschrift der Anwendung. Source Serif 4 ist für formale/redaktionelle Inhalte vorgesehen. Neuland wird nur über die Display-Rolle (`--font-family-display` / `.vdbs-display`) für kurze, große Markenüberschriften verwendet und nicht als normale UI-Schrift.

## Einmalige Migration der Fontdateien

`tools/sync_vdbs_fonts.php` prüft die erwarteten SHA-256-Prüfsummen. Bereits vorhandene korrekte Dateien aus `public/fonts/vdbs` werden in den Vite-Quellbereich übernommen. Fehlende Dateien werden aus den lokalen Originalarchiven ergänzt. Dadurch wird insbesondere `NeulandFont_2017.ttf` aus dem vorhandenen Neuland-Archiv ergänzt.

Nach erfolgreicher Synchronisierung entfernt `--cleanup` die alten öffentlichen TTF-Dateien und ausschließlich die beiden Font-Quellarchive aus dem Projekt-Root:

```bash
php tools/sync_vdbs_fonts.php --cleanup
```

Danach kann die Installation ohne Quellarchive geprüft werden:

```bash
php tools/sync_vdbs_fonts.php --check-only
```

Die Lizenzhinweise liegen gemeinsam mit den Font-Quellassets unter `resources/fonts/vdbs/licenses`.
