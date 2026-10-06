# Schuljahreswechsel (v0.5.5)

Unter `/verwaltung/schuljahreswechsel` (Link „Schuljahreswechsel vorbereiten“ auf „Schule und Schuljahre“, Recht `school.manage`, also Verwaltung) wechseln alle aktiven Ausleihkonten in einem Schritt in das neue Schuljahr.

## Ablauf

1. Das neue Schuljahr samt Klassen als Entwurf anlegen (wie bisher unter „Schule und Schuljahre“).
2. Die Seite zeigt je aktiver Klasse des laufenden Jahres die Zahl der aktiven Ausleihkonten und ein Ziel zur Auswahl. Vorgeschlagen wird:
   - Jahrgang 13 → **Ausscheiden (Abgang)**,
   - sonst die Klasse mit gleichem Namen und um eins höherem Jahrgang („5a“ → „6a“),
   - sonst die einzige Klasse des nächsten Jahrgangs,
   - sonst bleibt die Auswahl offen („— bitte wählen —“).
   Weitere Auswahlmöglichkeit: **Nicht ändern** (das Konto bleibt in seiner alten Klasse).
3. Nach Bestätigung (Häkchen) läuft der Wechsel **ganz oder gar nicht** in einer Transaktion: Ausleihkonten wechseln die Klasse, Abgänge werden ausgeschieden, danach wird das Zielschuljahr aktiv geschaltet. Die bestehenden Prüfungen der Aktivierung (aktive Klassen für alle weiterführenden Jahrgänge) gelten weiter.
4. Ergebnis: „n versetzt, m ausgeschieden, k unverändert“. Das Protokoll enthält ein Ereignis `school.year.transitioned`.

Nicht angefasst werden Ausleihkonten ohne Klasse (Lehrkräfte, Mitarbeiter:innen) und bereits ausgeschiedene Konten. Klassen ohne aktive Ausleihkonten brauchen keine Zuordnung.

## Ausscheiden

Abgänge laufen über dieselbe `DepartPatronAction` wie das einzelne Ausscheiden: Status und Austrittsdatum (heute), Klassenzuordnung entfällt, offene Aktivierungscodes werden widerrufen, das verknüpfte Onlinekonto wird deaktiviert.

Neu und für beide Wege gültig: Ein Ausleihkonto mit **offenen Ausleihen oder offenen Vormerkungen darf nicht ausscheiden**. Die Vorschau nennt betroffene Bibliotheksnummern mit Grund; der Wechsel bricht ab, solange es sie gibt. Technisch meldet das Modul Circulation diese Gründe über den Tag `patron.departure_guards` (`PatronDepartureGuard`), Patrons kennt Circulation nicht.

## Offen

- Der produktive Import von Schüler:innen und Klassen aus der Schulverwaltung (Dateiformat noch zu klären).
- Teilwechsel (nur einzelne Klassen) und das Zurücknehmen eines Wechsels gibt es nicht; die Vorschau soll das vorher sicher machen.
