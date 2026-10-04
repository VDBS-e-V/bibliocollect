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
            'description' => 'Erlaubt Änderungen an Ausleihkonten. Kritische Sperren bleiben später gesondert abgesichert.',
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
            'permissions' => ['surface.portal.access', 'surface.pos.access', 'patrons.lookup'],
        ],
        'student_ag_extended' => [
            'label' => 'Schüler-AG Erweitert',
            'permissions' => ['surface.portal.access', 'surface.pos.access', 'patrons.lookup'],
        ],
        'staff' => [
            'label' => 'Mitarbeiter:in',
            'permissions' => [
                'surface.portal.access',
                'surface.pos.access',
                'patrons.lookup',
                'patrons.sensitive.view',
                'patrons.manage',
                'patrons.link-code.issue',
                'identity.roles.assign',
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
                'patrons.link-code.issue',
                'identity.roles.assign',
                'school.manage',
            ],
        ],
        'technical_admin' => [
            'label' => 'Technische Administration',
            'permissions' => ['surface.administration.access'],
        ],
    ],
];
