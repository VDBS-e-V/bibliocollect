# T2 v0.3.4.1 - Font-Audit und Bereinigung

Beim Audit des aktuellen BiblioCollect-Stands wurden sieben der acht vorgesehenen VDBS-Webfonts gefunden: vier Lato-Schnitte und drei Source-Serif-4-Schnitte. Sie entsprechen in Dateiname und Dateigröße den bereitgestellten Originalarchiven und sind bereits über `@font-face` registriert.

`NeulandFont_2017.ttf` war dagegen im Repository nicht vorhanden, obwohl `font-faces.css` darauf verwiesen hat. Dadurch war die Display-Schrift nicht vollständig selbst gehostet; der Browser fiel dort auf Lato zurück.

Dieser Patch stellt die Font-Verarbeitung auf Vite-Quellassets um. Ein lokales Synchronisierungstool übernimmt die sieben bestehenden Dateien, ergänzt Neuland aus dem bereitgestellten Originalarchiv, prüft alle acht Dateien per SHA-256 und löscht anschließend die nicht mehr benötigten Font-Quell-ZIPs. Lizenzhinweise für Lato, Source Serif 4 und Neuland werden zusammen mit den Quellassets dokumentiert.

Das Paket enthält absichtlich keine Font-Binärdateien. Die vorhandenen Originalarchive auf dem Entwicklungsrechner sind die Quelle für die einmalige Synchronisierung.
