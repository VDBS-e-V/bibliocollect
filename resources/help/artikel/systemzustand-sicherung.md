---
titel: Systemzustand und Datensicherung
kurz: Läuft der Cron? Sicherung erstellen, herunterladen und wiederherstellen.
bereich: verwaltung
rollen: verwaltung
stichworte: systemzustand, sicherung, backup, cron, wiederherstellen, phpmyadmin
---

**Verwaltung → Systemzustand** zeigt, ob Cronjob, Warteschlange und Sicherung laufen, und die letzten Fehler. Dort kannst du Zeitplan-Aufgaben **einmal von Hand ausführen**, eine **Sicherung erstellen** und Sicherungen **herunterladen**. Lade sie regelmäßig herunter und lege sie außerhalb des Servers ab. Wiederherstellen: phpMyAdmin → Datenbank wählen → Importieren → die `.sql.gz`-Datei (vorher ist keine Migration nötig). Übe das einmal mit einer leeren Test-Datenbank.
