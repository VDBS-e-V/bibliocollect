# Hilfeartikel schreiben

Jede Datei in diesem Ordner ist ein Artikel der Hilfe im Bibliotheksbetrieb (`/betrieb/hilfe`). Der Dateiname ohne `.md` ist die Adresse (`etiketten-drucken.md` wird zu `/betrieb/hilfe/etiketten-drucken`). Diese Datei selbst hat keinen Kopf und erscheint deshalb nicht in der Hilfe.

## Aufbau

```
---
titel: Etiketten für Medien drucken
kurz: Etiketten mit Strichcode für die Bücher, auch auf Vorrat.
bereich: katalog
rollen: ag, mitarbeiter, verwaltung
stichworte: etikett, drucken, strichcode, vorrat
---

Text in Markdown. Zwischenüberschriften mit `##`.
```

- **titel**: erscheint als Überschrift und in der Liste. Nutzersicht, kurz.
- **kurz**: ein Satz für die Liste und unter der Überschrift.
- **bereich**: `grundlagen`, `ausleihe`, `konten`, `katalog` oder `verwaltung`.
- **rollen**: für wen der Artikel gedacht ist (`ag` = Schüler-AG, `mitarbeiter`, `verwaltung`), durch Komma getrennt. Danach filtert die Hilfe.
- **stichworte**: Wörter, unter denen man den Artikel suchen würde, auch Umgangssprache und Schreibweisen. Die Suche löst Umlaute auf (`ueberfaellig` findet „überfällig“).

## Schreibregeln

- Kurze Schritte, auf Deutsch, aus Sicht der Person am Tresen. Bedienelemente **fett** (**Vorgang bestätigen**).
- Nur beschreiben, was die Anwendung wirklich tut. Ändert sich eine Funktion, den Artikel im selben Pull Request anpassen.
- Keine echten Namen, Nummern oder Zugangsdaten. Das Repository ist öffentlich.
- Kein HTML: Es wird nicht ausgeführt, sondern angezeigt.
