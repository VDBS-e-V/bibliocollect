---
titel: Datenqualität nachts vorbereiten
kurz: Welche Qualitätsfälle der Cron zuerst bearbeitet.
bereich: verwaltung
rollen: verwaltung
stichworte: datenqualität, nacht, cron, anreicherung, zusammenfassung, vorschläge
---

Unter **Systemzustand → Datenqualität** siehst du, wie viele Fälle der Katalogqualität noch ohne Vorschlag sind. Hake die **Problemarten** an, die der Cron nachts **zuerst** bearbeiten soll (zum Beispiel „Zeichensatzfehler“), und stelle ein, wie viele Fälle pro Nacht drankommen. Mit „Auch Fälle nur zur Anreicherung“ holt das System außerdem Zusammenfassungen und Schlagwörter. Die Vorschläge landen in der **Katalogqualität**, dort prüfst und übernimmst du sie; der Katalog ändert sich nie von allein. Zusammenfassungen aus Google Books oder Open Library werden nicht gesammelt übernommen, du liest sie kurz. „Jetzt ein Stück abarbeiten“ startet einen Lauf sofort. Zeigt der Systemzustand „Sicherung außerhalb des Servers“ eine Warnung, lade eine Datenbanksicherung herunter und lege sie sicher ab.

Im selben Abschnitt zeigt die Tabelle **Sprachen im Bestand**, wo die DNB nichts findet. **Fälle ohne Treffer erneut versuchen** setzt diese Fälle zurück, damit der Cron sie mit Open Library und Google Books erneut nachschlägt (nur Ausgaben mit ISBN).
