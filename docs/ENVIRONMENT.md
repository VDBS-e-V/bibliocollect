# Environment-Dateien (`env:*`)

## Philosophie

`.env.example` ist die Vorlage für **Struktur, Reihenfolge und Kommentare**. Die Werte gehören der jeweiligen Umgebung und werden nie überschrieben, solange du es nicht ausdrücklich verlangst. Die Befehle zeigen nur Schlüsselnamen, **nie Werte**.

Welche Dateien gelten, steht in `config/foundation.php` unter `environment`: Vorlage (`.env.example`), Ziele (`.env`, `.env.testing`) und Sicherungsordner (`.foundation/env-backups`).

## Befehle

| Befehl | Zweck |
|---|---|
| `php artisan env:sync` | Ziele mit der Vorlage abgleichen |
| `php artisan env:check` | Prüfen, ob Schlüssel fehlen (Exit-Code 1 bei fehlenden Schlüsseln oder fehlender Datei) |
| `php artisan env:diff {target=.env}` | Missing und Extra einer Datei, nur Namen |
| `php artisan env:backup` | Zeitgestempelte Sicherung der Ziele |
| `php artisan env:restore` | Sicherung zurückspielen |

## Normaler Abgleich

```
php artisan env:sync
php artisan env:sync --target=.env
```

Neue Schlüssel aus `.env.example` werden ergänzt, vorhandene Werte bleiben, zusätzliche Schlüssel im Ziel bleiben erhalten (sie stehen am Ende unter „Weitere Einträge“). Fehlende Zieldateien werden angelegt. Ein zweiter Lauf ändert nichts mehr.

## Probelauf

`--dry-run` zeigt die geplanten Änderungen (ergänzt, ersetzt, entfernt) und schreibt nichts.

## Force

`--force` ersetzt vorhandene Werte durch die Werte aus der Vorlage. Das ist **destruktiv**: Es fragt nach (ohne Terminal nur mit `--yes`) und sichert die Zieldatei vorher automatisch.

## Prune

`--prune` entfernt Schlüssel, die nicht in der Vorlage stehen. Ebenfalls destruktiv, mit Rückfrage, `--yes` ohne Terminal und automatischer Sicherung.

## Sicherung

`php artisan env:backup [--target=.env ...]` legt `.foundation/env-backups/<name>.<JJJJMMTT-HHMMSS>.bak` an, z. B. `env.20261007-101500.bak`. Fehlende Dateien werden gemeldet und übersprungen. Mit `env:sync --backup` sichert auch ein normaler Abgleich vorher.

## Wiederherstellen

```
php artisan env:restore                        # neueste Sicherung für .env
php artisan env:restore env.20261007-101500.bak --target=.env
```

Immer mit Bestätigung (ohne Terminal `--yes`). Angegeben wird nur der **Dateiname** der Sicherung. Der bisherige Stand wird vorher selbst gesichert, damit auch ein Restore zurückgenommen werden kann.

## Sicherheitsregeln

- Keine Werte in der Ausgabe, auch nicht bei Fehlern.
- Sicherungen enthalten Geheimnisse: `/.foundation/env-backups/` steht in `.gitignore` und darf nie committet werden. Die Dateien bekommen, wo möglich, die Rechte 0600.
- Environment-Inhalte gehören in keinen allgemeinen Verlauf (Tooling-History, Tickets, Chats).
- Pfade müssen im Projekt liegen: `..`, absolute Pfade und Laufwerksangaben (`C:\…`) werden abgelehnt.
- Zerstörende Aktionen brauchen Bestätigung, in Skripten `--yes`.
