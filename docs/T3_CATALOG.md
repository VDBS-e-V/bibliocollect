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

v0.4.2 hat die Pflege von `Title` und `Edition` eingeführt. v0.4.3 ergänzt die Verantwortlichenpflege. v0.4.4 ergänzt die Pflege physischer `Copy`-Datensätze. Änderungen laufen jeweils über eigene Catalog-Actions und validierte DTOs; die HTTP-Validierung bleibt in der POS-Surface.

Löschfunktionen werden nicht pauschal angeboten. Bei Verantwortlichen wird nur die Titelverknüpfung gelöst und ein verwaister Contributor kontrolliert bereinigt. Physische Exemplare werden überhaupt nicht hart gelöscht.

## Verantwortlichenpflege

Ab v0.4.3 können Verantwortliche innerhalb der Titelpflege angelegt, bearbeitet und vom Titel gelöst werden. `display_name` und optional `sort_name` gehören zum wiederverwendbaren `Contributor`; `role_key` und `position` gehören zur konkreten `TitleContribution`.

`role_key` bleibt weiterhin ein offener technischer Schlüssel. Die Oberfläche normalisiert ihn auf Kleinbuchstaben und erlaubt Buchstaben, Ziffern, Punkt, Unterstrich und Bindestrich. Dadurch bleiben spätere Import-Mappings möglich, ohne früh ein starres Rollen-Enum einzuführen.

Wird ein Contributor bearbeitet, ändern sich seine Namensdaten an allen Titeln, die denselben Datensatz verwenden. Das Bearbeitungsformular weist darauf hin, wenn der Contributor an mehreren Titeln genutzt wird. Beim Entfernen eines Verantwortlichen wird nur die Titelverknüpfung gelöst; ein danach verwaister Contributor wird automatisch bereinigt.

## Exemplarpflege

Ab v0.4.4 werden physische Exemplare innerhalb ihrer `Edition` gepflegt. Die editierbaren Felder sind:

- `barcode`: katalogweit eindeutig und sichtbar, aber niemals Primärschlüssel,
- `shelf_location`: optionaler Regal- oder Standortwert,
- `status`: einer der vorhandenen Werte `active`, `damaged`, `lost` oder `withdrawn`.

Ein `Copy` kann in diesem Workflow nicht auf eine andere Ausgabe verschoben werden. Das ist absichtlich keine Nebenwirkung eines normalen Bearbeitungsformulars. Sollte ein solcher Fachworkflow später benötigt werden, braucht er eine eigene Action mit expliziten Regeln.

Es gibt keine Hard-Delete-Route für Exemplare. `withdrawn` repräsentiert dauerhaft ausgesonderten Bestand, ohne die Exemplaridentität zu verlieren. Das ist wichtig, weil spätere Circulation-, Inventur- und Schadenshistorien auf derselben internen Copy-ULID aufbauen sollen.

Der aktuelle `CopyStatus` beschreibt den Katalog-/Bestandszustand. T4 Circulation muss zusätzlich den tatsächlichen Ausleihzustand berücksichtigen; ein Statuswert allein ist noch keine vollständige Verfügbarkeitsentscheidung.

Barcode-Duplikate werden nicht nur durch den Datenbankindex verhindert. `CreateCopyAction` und `UpdateCopyAction` prüfen die Eindeutigkeit fachlich und liefern der Oberfläche einen verständlichen Fehler. Die Datenbank-Unique-Constraint bleibt die letzte technische Sicherung.
