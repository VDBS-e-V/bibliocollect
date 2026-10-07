<?php

declare(strict_types=1);

return [
    'permissions' => [
        'surface.portal.access' => [
            'label' => 'Portal öffnen',
            'description' => 'Erlaubt den Zugang zum persönlichen BiblioCollect-Portal.',
        ],
        'surface.pos.access' => [
            'label' => 'Bibliotheksbetrieb öffnen',
            'description' => 'Erlaubt den Zugang zur Arbeitsoberfläche für Ausleihe und Bibliotheksbetrieb.',
        ],
        'surface.administration.access' => [
            'label' => 'Verwaltung öffnen',
            'description' => 'Erlaubt den Zugang zur Verwaltungsoberfläche. Fachliche Einzelrechte bleiben separat.',
        ],
        'patrons.lookup' => [
            'label' => 'Ausleihkonten nachschlagen',
            'description' => 'Erlaubt die einfache Suche nach Ausleihkonten im Bibliotheksbetrieb.',
        ],
        'patrons.sensitive.view' => [
            'label' => 'Sensible Ausleihkontodaten sehen',
            'description' => 'Erlaubt die Anzeige von Geburtsdatum, E-Mail, Austrittsdatum und Sperrgrund.',
        ],
        'patrons.manage' => [
            'label' => 'Ausleihkonten verwalten',
            'description' => 'Erlaubt das Anlegen und Bearbeiten fachlicher Stammdaten von Ausleihkonten.',
        ],
        'patrons.block' => [
            'label' => 'Ausleihkonten sperren',
            'description' => 'Erlaubt das kritische Sperren und Entsperren eines Ausleihkontos mit protokolliertem Grund.',
        ],
        'patrons.depart' => [
            'label' => 'Ausleihkonten dauerhaft ausscheiden',
            'description' => 'Erlaubt den kontrollierten dauerhaften Austritt eines Ausleihkontos mit Statusprotokoll und Deaktivierung des verknüpften Onlinekontos.',
        ],
        'patrons.link-code.issue' => [
            'label' => 'Onlinekonto-Code ausgeben',
            'description' => 'Erlaubt die Ausgabe eines einmaligen Codes zur Verknüpfung eines Onlinekontos mit einem bestehenden Ausleihkonto.',
        ],
        'identity.roles.assign' => [
            'label' => 'Rollen zuweisen',
            'description' => 'Erlaubt die kontrollierte Zuweisung kombinierbarer Rollen an Onlinekonten.',
        ],
        'school.manage' => [
            'label' => 'Schuldaten verwalten',
            'description' => 'Erlaubt die Verwaltung von Schuljahren, Klassen und Öffnungstagen.',
        ],
        'catalog.manage' => [
            'label' => 'Katalog pflegen',
            'description' => 'Erlaubt das Anlegen und Bearbeiten bibliografischer Titel- und Editionsdaten im Bibliotheksbetrieb.',
        ],
        'catalog.import' => [
            'label' => 'Katalog importieren',
            'description' => 'Erlaubt vorbereitete Massenimporte in den Katalog mit Mapping, Vorschau, Konfliktprüfung und expliziter Übernahme.',
        ],
        'circulation.reports' => [
            'label' => 'Klassenlisten drucken',
            'description' => 'Erlaubt Sammellisten offener Ausleihen je Klasse für die Klassenleitungen.',
        ],
        'content.manage' => [
            'label' => 'Informationsseiten bearbeiten',
            'description' => 'Erlaubt das Bearbeiten von Impressum, Datenschutzerklärung und Erklärung zur Barrierefreiheit.',
        ],
        'wishes.manage' => [
            'label' => 'Buchwünsche bearbeiten',
            'description' => 'Erlaubt, Buchwünsche anzusehen, selbst zu erfassen und den Stand zu setzen.',
        ],
        'statistics.view' => [
            'label' => 'Statistik einsehen',
            'description' => 'Erlaubt Kennzahlen zur Ausleihe und zum Bestand (nur Zählwerte, keine Personen).',
        ],
        'audit.view' => [
            'label' => 'Protokoll einsehen',
            'description' => 'Erlaubt die Einsicht in das Protokoll fachlicher Ereignisse (Ausleihe, Katalog, Schule).',
        ],
        'circulation.manage' => [
            'label' => 'Ausleihe und Rückgabe durchführen',
            'description' => 'Erlaubt das Ausleihen und Zurückgeben physischer Exemplare im Bibliotheksbetrieb.',
        ],
    ],

    // Rollen sind kombinierbare Permission-Bundles. Fachfunktionen prüfen Permissions, nie Rollennamen.
    'roles' => [
        'student' => [
            'label' => 'Schüler:in',
            'permissions' => ['surface.portal.access'],
        ],
        'teacher' => [
            'label' => 'Lehrkraft',
            'permissions' => ['surface.portal.access'],
        ],
        'student_ag_basic' => [
            'label' => 'Schüler-AG Basis',
            'permissions' => ['surface.portal.access', 'surface.pos.access', 'patrons.lookup', 'circulation.manage'],
        ],
        'student_ag_extended' => [
            'label' => 'Schüler-AG Erweitert',
            'permissions' => ['surface.portal.access', 'surface.pos.access', 'patrons.lookup', 'catalog.manage', 'circulation.manage'],
        ],
        'staff' => [
            'label' => 'Mitarbeiter:in',
            'permissions' => [
                'surface.portal.access',
                'surface.pos.access',
                'patrons.lookup',
                'patrons.sensitive.view',
                'patrons.manage',
                'patrons.block',
                'patrons.depart',
                'patrons.link-code.issue',
                'identity.roles.assign',
                'catalog.manage',
                'catalog.import',
                'circulation.manage',
                'circulation.reports',
                'statistics.view',
                'wishes.manage',
            ],
        ],
        'management' => [
            'label' => 'Verwaltung',
            'permissions' => [
                'surface.portal.access',
                'surface.pos.access',
                'surface.administration.access',
                'patrons.lookup',
                'patrons.sensitive.view',
                'patrons.manage',
                'patrons.block',
                'patrons.depart',
                'patrons.link-code.issue',
                'identity.roles.assign',
                'catalog.manage',
                'catalog.import',
                'circulation.manage',
                'school.manage',
                'audit.view',
                'content.manage',
                'circulation.reports',
                'statistics.view',
                'wishes.manage',
            ],
        ],
        'technical_admin' => [
            'label' => 'Technische Administration',
            'permissions' => ['surface.administration.access'],
        ],
    ],
];
