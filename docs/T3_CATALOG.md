# T3 – Catalog

## Domänengrenzen

Der Katalog trennt drei Ebenen strikt:

- `Title`: titelbezogene bibliografische Identität und Verantwortlichkeiten.
- `Edition`: konkrete Ausgabe mit ausgabebezogenen Angaben wie ISBN, Verlag, Erscheinungsjahr, Medientyp, Sprachcode und Altersfreigabe.
- `Copy`: physisches Exemplar mit eigenem Barcode, Standort und Exemplarstatus.

Öffentliche Recherche und spätere Reservierungen werden titelbezogen aufgebaut. Ausleihe, Rückgabe, Inventur und Schäden arbeiten exemplarbezogen.

## Verantwortliche

`Contributor` ist absichtlich neutral benannt und kann Personen oder Körperschaften repräsentieren. `TitleContribution` verbindet einen Verantwortlichen mit einem Titel, speichert eine flexible `role_key` und eine Position für die Anzeigereihenfolge.

Die Rollen werden in v0.4.1 noch nicht als starres Enum begrenzt. Damit bleiben spätere CSV-/MARC21-Importe offen für weitere Verantwortlichkeitsarten, ohne das Domänenmodell sofort ändern zu müssen.

## Editionsmetadaten

`media_type` und `language_code` sind in v0.4.1 bewusst offene, kurze Zeichenketten. Normalisierung, kontrollierte Vokabulare und Import-Mappings folgen erst mit der Katalogpflege bzw. dem Import-Workflow.

Die vorhandenen Felder `minimum_age` und `age_rating_label` bleiben an `Edition`. Eine harte Ausleihentscheidung wird erst in Circulation gegen das Geburtsdatum des Patrons ausgewertet.

## Suche

`SearchCatalogTitlesQuery` liefert immer `Title`-Datensätze zurück. Gesucht wird über Titel, Untertitel, Sortiertitel, Verantwortliche sowie ausgewählte Editionsfelder. Physische Barcodes sind bewusst nicht Teil dieser titelbasierten Recherche.

Die Query entfernt SQL-LIKE-Wildcards aus Benutzereingaben, verlangt mindestens zwei Buchstaben/Ziffern und begrenzt die Treffermenge.

## Interne Katalogpflege

Ab v0.4.2 liegt die Katalogpflege im Surface `Bibliotheksbetrieb` und nicht in der Systemverwaltung. Das Fachrecht `catalog.manage` wird Schüler-AG Erweitert, Mitarbeiter:innen und Verwaltung zugewiesen. Schüler-AG Basis und technische Administration erhalten es nicht.

Der erste Pflegeworkflow umfasst ausschließlich `Title` und `Edition`. Änderungen laufen über eigene Actions und DTOs; HTTP-Validierung bleibt in der POS-Surface. Es gibt in diesem Schritt keine Löschfunktionen und noch keinen Editor für Verantwortliche oder Exemplare.

## Verantwortlichenpflege

Ab v0.4.3 können Verantwortliche innerhalb der Titelpflege angelegt, bearbeitet und vom Titel gelöst werden. `display_name` und optional `sort_name` gehören zum wiederverwendbaren `Contributor`; `role_key` und `position` gehören zur konkreten `TitleContribution`.

`role_key` bleibt weiterhin ein offener technischer Schlüssel. Die Oberfläche normalisiert ihn auf Kleinbuchstaben und erlaubt Buchstaben, Ziffern, Punkt, Unterstrich und Bindestrich. Dadurch bleiben spätere Import-Mappings möglich, ohne früh ein starres Rollen-Enum einzuführen.

Wird ein Contributor bearbeitet, ändern sich seine Namensdaten an allen Titeln, die denselben Datensatz verwenden. Das Bearbeitungsformular weist darauf hin, wenn der Contributor an mehreren Titeln genutzt wird. Beim Entfernen eines Verantwortlichen wird nur die Titelverknüpfung gelöst; ein danach verwaister Contributor wird automatisch bereinigt.
