---
titel: Reihen und Bände
kurz: Wie Buchreihen erkannt werden, wie Leser:innen den nächsten Band finden und wie du Verlagsreihen ausblendest.
bereich: katalog
rollen: ag, mitarbeiter, verwaltung
stichworte: reihe, reihen, band, bände, serie, verlagsreihe, taschenbuch, nächster band
---

Aus der **Reihenangabe** einer Ausgabe (Feld „Reihe“, zum Beispiel „Die Schule der magischen Tiere ; 3“) leitet BiblioCollect beim Speichern die **Reihe** und die **Bandnummer** ab. Schreibvarianten („Gullivers Bu?cher“, „Gullivers Bücher“, „[Fischer]“) fallen zusammen; reine Zahlen gelten nicht als Reihe.

**Für Leser:innen**

- Auf der Titelseite steht „Teil der Reihe …, Band 3“ mit Link.
- Die Reihenseite (`/reihe/<name>`) zeigt alle Bände nach Bandnummer mit Cover und Verfügbarkeit. Wer angemeldet ist und schon Bände der Reihe ausgeliehen hat, sieht den Hinweis „Nächster Band“.
- `/reihen` listet alle Reihen mit mindestens zwei Titeln im Bestand (Link im Katalog neben „Erweiterte Suche“).

**Pflege** (Menü „Reihen prüfen“ im Bereich Katalog, Recht „Katalog bearbeiten“)

- Der Altbestand führt unter „Reihe“ oft **Verlags- und Taschenbuchreihen** („dtv“, „Fischer“, „Ravensburger Taschenbuch“). Solche Reihen sind von Anfang an ausgeblendet; sie erscheinen nicht als „Teil der Reihe“. Was du hier sonst noch ausblendest, bleibt ausgeblendet.
- Du kannst den **Namen** einer Reihe korrigieren (er gilt für alle Bände).
- „Zuordnung neu berechnen“ ordnet alle Ausgaben neu zu, zum Beispiel nach einem Import. Dasselbe macht `php artisan catalog:series:sync`.
- Die Reihenangabe selbst änderst du an der Ausgabe; die Zuordnung folgt automatisch.
