# T2 – Mitarbeiteroberfläche für Ausleihkonten

Stand: v0.3.2

## Zweck

Der Bibliotheksbetrieb erhält erstmals eine echte, serverseitig geschützte Oberfläche zum gezielten Nachschlagen von Ausleihkonten. Die Oberfläche ist bewusst kein allgemeines Personenverzeichnis.

## Need-to-know

- `student_ag_basic` und `student_ag_extended` dürfen gezielt nach Bibliotheksnummer oder Name suchen und Basisdaten sehen. Die Suche verlangt mindestens zwei Buchstaben/Ziffern, entfernt LIKE-Wildcards und liefert höchstens 25 Treffer.
- Geburtsdatum, E-Mail, Austrittsdatum und Sperrgrund benötigen `patrons.sensitive.view`.
- Diese Permission liegt in v0.3.2 nur bei `staff` und `management`.
- Technische Admin-Konten erhalten dadurch keine Patron-Sichtbarkeit.

## Onlinekonto-Verknüpfung

Mitarbeiter:innen und Verwaltung können für geeignete, aktive Schüler:innen- und Lehrkraftkonten einen einmaligen Verknüpfungscode ausgeben.

- Der Klartext-Code wird nicht gespeichert.
- Ein neuer Code widerruft einen vorherigen offenen Code.
- Bereits mit einem Onlinekonto verknüpfte Patrons erhalten keinen weiteren Code.
- Der Klartext-Code wird direkt in der POST-Antwort angezeigt, weder in Session/Flash noch in der URL gespeichert und mit `Cache-Control: no-store` ausgeliefert.

## AG-Rollen

Auf der Patron-Seite werden ausschließlich die Rollen `student_ag_basic` und `student_ag_extended` verwaltet. Grundrollen sowie Mitarbeiter-, Verwaltungs- und technische Rollen sind nicht über diesen Workflow zuweisbar. Damit kann eine Mitarbeiterin oder ein Mitarbeiter insbesondere die kompetenzbasierte Freigabe `Schüler-AG Erweitert` erteilen, ohne einen allgemeinen Rollen-Editor zu erhalten.

## Noch nicht enthalten

- Stammdatenbearbeitung
- Sperren/Entsperren
- Klassenwechsel und Schuljahresübergang
- Audit-Ansicht
- Ausleihe/Rückgabe

Diese Funktionen folgen in ihren jeweiligen fachlichen Workflows.
