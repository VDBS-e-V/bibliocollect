# T2 v0.3.4.2

Kleiner Portabilitäts-Hotfix: SQLite gibt ein Eloquent-`date` im Rohwert des Tests als `YYYY-MM-DD 00:00:00` zurück, während MySQL/MariaDB für eine DATE-Spalte typischerweise `YYYY-MM-DD` liefert. Der Test prüft deshalb künftig die fachliche Datumsrepräsentation unabhängig vom DB-Rohformat.

Die Font-Integration aus v0.3.4.1 bleibt unverändert.
