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
