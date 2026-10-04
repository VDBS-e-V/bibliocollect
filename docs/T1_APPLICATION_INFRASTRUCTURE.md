# T1 – Anwendungsinfrastruktur

T1 baut die gemeinsame Infrastruktur für alle BiblioCollect-Surfaces auf, ohne bereits Fachlogik für Katalog, Ausleihe oder Konten zu implementieren.

## Berechtigungen

Permissions sind stabile technische Schlüssel. Rollen sind kombinierbare Bundles dieser Schlüssel. Fachcode darf später Permissions prüfen, aber keine Rollennamen vergleichen. Die technische Administration erhält durch ihre Rolle nur Zugang zur Verwaltungs-Surface; daraus entsteht ausdrücklich kein automatischer Zugriff auf Ausleih- oder Lesedaten.

Die Persistenz von Rollen und Permissions auf Onlinekonten folgt in T2. Bis dahin verweigern die produktiven Portal-, POS- und Verwaltungsrouten ohne autorisierten Benutzer serverseitig den Zugriff.

## Navigation und Surfaces

Die Navigation wird zentral aus `config/navigation.php` aufgebaut. BiblioCollect besitzt vier Surfaces:

- Public: öffentlicher Katalog und öffentliche Inhalte
- Portal: persönliche Selbstbedienung
- Pos: scanner- und tastaturorientierter Bibliotheksbetrieb
- Administration: Regeln, Importe, Datenschutz und Verwaltung

Lokale Preview-Routen unter `/_preview/*` werden nur bei `APP_ENV=local` registriert. Sie dienen ausschließlich zur visuellen Entwicklung und existieren nicht in Test- oder Produktionsumgebungen.

## Zeit

Technische Zeitstempel bleiben in der Laravel-Anwendung weiterhin UTC-orientiert. Für fachliche Regeln wie Öffnungstage, Fälligkeitstage und Ferien stellt `BusinessClock` explizit die konfigurierte Fachzeitzone bereit. Standard ist `Europe/Berlin`.

## UI

Die App-Shell nutzt die vorhandenen VDBS-Farbtokens, lokal ausgelieferte Schriften und den manuellen Light/Dark-Umschalter. Die Oberfläche ist bewusst funktional aufgebaut: schmale Utility-Leiste, klarer Marken-/Produktkopf, horizontale Hauptnavigation, prominente Katalogsuche und ruhige Inhaltsbereiche. Runde "Dashboard-Karten" und dekorative Effekte werden vermieden; VDBS-Grün und -Lila strukturieren stattdessen Suche, aktive Navigation und Abschnittsüberschriften.

Für die Informationsarchitektur dienen etablierte Bibliotheksoberflächen wie FU-/HU-Primo, der VÖBB-Katalog und besonders der Webauftritt der Universitätsbibliothek der TU Berlin als Orientierung. Suche steht früh und deutlich im Ablauf, Suchbereich und Suchbegriff sind getrennt. Die öffentliche Oberfläche nutzt großzügige Weißräume, klare Abschnittsüberschriften und wenige, ruhige Markenakzente; Arbeitsbereiche bleiben dichter und nutzen Tabellen, Listen, Seiten-/Bereichsnavigation und eindeutige Statusinformationen. Das visuelle Erscheinungsbild bleibt dabei eigenständig VDBS.

Basis-Komponenten decken Buttons, Karten/Arbeitsflächen, Formfelder, Alerts, Badges, Tabellen und Seitenköpfe ab. Formfehler werden zusätzlich zum visuellen Zustand immer mit dem Wort „Fehler:“ ausgegeben.
