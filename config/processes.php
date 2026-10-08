<?php

declare(strict_types=1);

/*
 * Alle Vorgänge der Bibliothek in zwei getrennten Bereichen: „Betrieb“ ist das, was am Ausleihplatz täglich passiert,
 * „Verwaltung“ ist Einrichten, Pflegen und Auswerten. Die Seite „Alle Vorgänge“ zeigt nur, was die angemeldete Person
 * darf. Die Hauptnavigation enthält die wichtigsten Vorgänge (config/navigation.php).
 */
return [
    'areas' => [
        [
            'key' => 'betrieb',
            'title' => 'Betrieb',
            'lead' => 'Was am Ausleihplatz und im Katalog täglich gebraucht wird.',
            'groups' => [
                [
                    'title' => 'Ausleihe',
                    'items' => [
                        ['label' => 'Ausleihe und Rückgabe', 'text' => 'Person oder Ausweis scannen, Medien ausleihen, verlängern und zurücknehmen, Beleg drucken oder mailen.', 'route' => 'pos.terminal', 'permission' => 'circulation.manage'],
                        ['label' => 'Rückgabe ohne Person', 'text' => 'Zurückgebrachte Medien nacheinander scannen und gemeinsam bestätigen.', 'route' => 'pos.terminal', 'permission' => 'circulation.manage'],
                        ['label' => 'Vormerkungen bearbeiten', 'text' => 'Zurückgelegte Medien zur Abholung und die Warteschlangen im Blick.', 'route' => 'pos.reservations.index', 'permission' => 'circulation.manage'],
                        ['label' => 'Buchwunsch erfassen', 'text' => 'Das öffentliche Formular, auch für Leser:innen ohne Anmeldung; mit ISBN-Suche.', 'route' => 'public.wishes.create', 'permission' => null],
                        ['label' => 'Buchwünsche bearbeiten', 'text' => 'Wünsche von Leser:innen ansehen, annehmen, bestellen oder ablehnen.', 'route' => 'pos.wishes.index', 'permission' => 'wishes.manage'],
                        ['label' => 'Überfällige Ausleihen ansehen', 'text' => 'Wer hat etwas überfällig? Die Liste für alle am Ausleihplatz, am längsten Überfälliges zuerst.', 'route' => 'pos.overdue', 'permission' => 'circulation.manage'],
                        ['label' => 'Überfällige Medien', 'text' => 'Klassenlisten mit überfälligen Ausleihen zum Weitergeben an die Klassenleitungen.', 'route' => 'pos.reports.class-loans', 'permission' => 'circulation.reports'],
                    ],
                ],
                [
                    'title' => 'Ausweise und Personen',
                    'items' => [
                        ['label' => 'Ausweis registrieren', 'text' => 'Einen neuen Ausweis am Ausleihplatz scannen und einer Person zuordnen.', 'route' => 'pos.terminal', 'permission' => 'circulation.manage'],
                        ['label' => 'Ausweise klassenweise ausgeben', 'text' => 'Klasse wählen und die Ausweise nacheinander neben den Namen scannen; zeigt auch, wer noch keinen hat.', 'route' => 'pos.labels.cards.issue', 'permission' => 'circulation.manage'],
                        ['label' => 'Ausweis verloren oder ersetzen', 'text' => 'Ausleihkonto öffnen und im Abschnitt „Ausweise“ sperren oder einen neuen ausstellen.', 'route' => 'pos.patrons.index', 'permission' => 'patrons.lookup'],
                        ['label' => 'Ausleihkonto suchen', 'text' => 'Personen nach Name, Klasse oder Bibliotheksnummer finden.', 'route' => 'pos.patrons.index', 'permission' => 'patrons.lookup'],
                    ],
                ],
                [
                    'title' => 'Katalog',
                    'items' => [
                        ['label' => 'Medium erfassen', 'text' => 'Neuzugang per ISBN oder Titel aufnehmen, Exemplar anlegen, Etikett drucken.', 'route' => 'pos.catalog.intake.identify', 'permission' => 'catalog.manage'],
                        ['label' => 'Katalog suchen und pflegen', 'text' => 'Titel, Ausgaben und Exemplare ausführlich recherchieren und bearbeiten.', 'route' => 'pos.catalog.index', 'permission' => 'catalog.manage'],
                        ['label' => 'Metadaten prüfen', 'text' => 'Fehlerhafte und lückenhafte Katalogdaten mit Vorschlägen durchgehen.', 'route' => 'pos.catalog.quality.index', 'permission' => 'catalog.manage'],
                        ['label' => 'Medien einsortieren', 'text' => 'Neu erfasste Bücher vom Stapel ins Regal stellen: Regalbrett wählen, Bücher scannen, Standort wird vermerkt.', 'route' => 'pos.shelving', 'permission' => 'circulation.manage'],
                        ['label' => 'Inventur', 'text' => 'Bestand und Regal abgleichen: Regalbrett wählen, Bücher scannen, Bericht über Fehlendes, falsch Einsortiertes und Unbekanntes.', 'route' => 'pos.inventory', 'permission' => 'inventory.count'],
                        ['label' => 'Bücher aussondern', 'text' => 'Alte, beschädigte oder doppelte Bücher mit Grund und Verbleib aus dem Bestand nehmen; Liste für den Jahresbericht.', 'route' => 'pos.withdrawal', 'permission' => 'catalog.withdraw'],
                        ['label' => 'Etiketten auf Vorrat drucken', 'text' => 'Inventarnummern im Voraus drucken (Vorlauf). Das System überspringt vergebene Nummern; einmalig lassen sich die Lücken der laufenden Reihe füllen.', 'route' => 'pos.labels.stock', 'permission' => 'catalog.manage'],
                        ['label' => 'Etiketten drucken', 'text' => 'Exemplar-Etiketten mit Strichcode und Standort auf Etikettenbögen.', 'route' => 'pos.labels.copies', 'permission' => 'catalog.manage'],
                        ['label' => 'Öffentlichen Katalog öffnen', 'text' => 'So sehen Leser:innen den Katalog.', 'route' => 'public.catalog.index', 'permission' => null],
                    ],
                ],
                [
                    'title' => 'Hilfe',
                    'items' => [
                        ['label' => 'Anleitungen', 'text' => 'Schritt-für-Schritt-Hinweise für Ausleihe, Katalog und Verwaltung.', 'route' => 'pos.help', 'permission' => 'surface.pos.access'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'verwaltung',
            'title' => 'Verwaltung',
            'lead' => 'Einrichten, Pflegen, Auswerten und Nachweisen. Wird seltener gebraucht und ist nur für Mitarbeiter:innen und Verwaltung.',
            'groups' => [
                [
                    'title' => 'Ausleihkonten und Ausweise',
                    'items' => [
                        ['label' => 'Regeln der Bibliothek', 'text' => 'Leihfristen, Höchstzahlen, Verlängern, Vormerken und Erinnerungen im Web einstellen.', 'route' => 'administration.rules.index', 'permission' => 'settings.manage'],
                        ['label' => 'Benutzerkonten verwalten', 'text' => 'Mitarbeitende einladen, Rollen vergeben, Konten deaktivieren oder wieder aktivieren.', 'route' => 'administration.users.index', 'permission' => 'users.manage'],
                        ['label' => 'Ausleihkonto anlegen', 'text' => 'Eine einzelne Person aufnehmen, die vor dir steht: Konto anlegen und gleich den Ausweis scannen.', 'route' => 'pos.patrons.create', 'permission' => 'patrons.manage'],
                        ['label' => 'Klassendaten importieren', 'text' => 'Von den Klassenleitungen ausgefüllte Vorlage einlesen und vor dem Übernehmen prüfen. Die Ausweise folgen, wenn die Klasse da ist.', 'route' => 'pos.patrons.import.create', 'permission' => 'patrons.manage'],
                        ['label' => 'Ausweise erzeugen und drucken', 'text' => 'Chargen mit Zufallsnummern anlegen, Bögen drucken, Nummern als Liste exportieren.', 'route' => 'pos.labels.cards', 'permission' => 'patrons.manage'],
                        ['label' => 'Motive der Ausweise', 'text' => 'Gestaltung der Vorder- und Rückseiten hochladen und ein- oder ausschalten.', 'route' => 'pos.labels.cards.designs', 'permission' => 'patrons.manage'],
                    ],
                ],
                [
                    'title' => 'Bestand',
                    'items' => [
                        ['label' => 'Regalbretter pflegen', 'text' => 'Die Liste der Regalbretter anlegen, die beim Erfassen als Standort gewählt werden.', 'route' => 'administration.shelves.index', 'permission' => 'shelves.manage'],
                        ['label' => 'Themenbereiche pflegen', 'text' => 'Die Themenbereiche anlegen und ändern, die ein Medium beim Erfassen bekommt. Welche Regalbretter dazu gehören, stellst du bei den Regalbrettern ein.', 'route' => 'administration.topics.index', 'permission' => 'shelves.manage'],
                        ['label' => 'Alte Inventarnummern umstellen', 'text' => 'Exemplare mit alter Nummer ausdrücklich auf neue siebenstellige Nummern umstellen und Etiketten drucken.', 'route' => 'administration.inventory.index', 'permission' => 'inventory.renumber'],
                        ['label' => 'Katalog importieren', 'text' => 'Bestandsdaten aus einer Datei einlesen.', 'route' => 'pos.catalog.import.create', 'permission' => 'catalog.import'],
                        ['label' => 'Altbestand übernehmen', 'text' => 'Den Katalog und die Buchwünsche aus dem alten BiblioCollect (JSON-Export) hochladen, prüfen und übernehmen.', 'route' => 'pos.catalog.legacy.create', 'permission' => 'catalog.import'],
                    ],
                ],
                [
                    'title' => 'Auswertungen',
                    'items' => [
                        ['label' => 'Statistik', 'text' => 'Ausleihen, beliebte Titel, Klassen, Medientypen und Bestand für einen Zeitraum; Export und Druck.', 'route' => 'pos.statistics', 'permission' => 'statistics.view'],
                        ['label' => 'E-Mail-Vorschau', 'text' => 'Alle E-Mails der Anwendung mit Beispielangaben ansehen, ohne etwas zu verschicken.', 'route' => 'administration.mail-preview', 'permission' => 'system.view'],
                        ['label' => 'Systemzustand', 'text' => 'Läuft der Cron, wartet etwas in der Warteschlange, ist die Sicherung aktuell, gab es Fehler?', 'route' => 'administration.system.index', 'permission' => 'system.view'],
                        ['label' => 'Protokoll', 'text' => 'Wer hat wann was geändert.', 'route' => 'administration.audit.index', 'permission' => 'audit.view'],
                    ],
                ],
                [
                    'title' => 'Schule und Öffnungszeiten',
                    'items' => [
                        ['label' => 'Schuljahreswechsel', 'text' => 'Klassen weiterführen, Abgänge beenden, neues Schuljahr aktivieren.', 'route' => 'administration.transition.show', 'permission' => 'school.manage'],
                        ['label' => 'Schuljahre und Klassen', 'text' => 'Schuljahre, Klassen und Klassenleitungen pflegen.', 'route' => 'administration.school.index', 'permission' => 'school.manage'],
                        ['label' => 'Öffnungszeiten und Schließtage', 'text' => 'Öffnungszeiten je Wochentag und Ferien oder Schließtage.', 'route' => 'administration.calendar.index', 'permission' => 'school.manage'],
                    ],
                ],
                [
                    'title' => 'Inhalte',
                    'items' => [
                        ['label' => 'Informationsseiten', 'text' => 'Impressum, Datenschutz und Barrierefreiheit bearbeiten.', 'route' => 'administration.pages.index', 'permission' => 'content.manage'],
                    ],
                ],
            ],
        ],
    ],
];
