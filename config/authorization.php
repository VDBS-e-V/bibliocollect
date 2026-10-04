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
            'permissions' => ['surface.portal.access', 'surface.pos.access'],
        ],
        'student_ag_extended' => [
            'label' => 'Schüler-AG Erweitert',
            'permissions' => ['surface.portal.access', 'surface.pos.access'],
        ],
        'staff' => [
            'label' => 'Mitarbeiter:in',
            'permissions' => ['surface.portal.access', 'surface.pos.access'],
        ],
        'management' => [
            'label' => 'Verwaltung',
            'permissions' => ['surface.portal.access', 'surface.pos.access', 'surface.administration.access'],
        ],
        'technical_admin' => [
            'label' => 'Technische Administration',
            'permissions' => ['surface.administration.access'],
        ],
    ],
];
