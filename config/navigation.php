<?php

declare(strict_types=1);

return [
    'surfaces' => [
        'public' => [
            'label' => 'Katalog',
            'items' => [
                [
                    'label' => 'Start',
                    'route' => 'public.home',
                    'active' => 'public.home',
                    'order' => 10,
                ],
                [
                    'label' => 'Katalog',
                    'route' => 'public.catalog.index',
                    'active' => 'public.catalog.*',
                    'order' => 20,
                ],
                [
                    'label' => 'Buchwunsch',
                    'route' => 'public.wishes.create',
                    'active' => 'public.wishes.*',
                    'order' => 30,
                ],
            ],
        ],
        'portal' => [
            'label' => 'Mein Konto',
            'items' => [
                [
                    'label' => 'Übersicht',
                    'route' => 'portal.home',
                    'preview_route' => 'preview.portal',
                    'active' => 'portal.*',
                    'permission' => 'surface.portal.access',
                    'order' => 10,
                ],
            ],
        ],
        'pos' => [
            'label' => 'Bibliotheksbetrieb',
            'items' => [
                [
                    'label' => 'Arbeitsplatz',
                    'route' => 'pos.home',
                    'preview_route' => 'preview.pos',
                    'active' => 'pos.home',
                    'permission' => 'surface.pos.access',
                    'order' => 10,
                ],
                [
                    'label' => 'Ausleihe und Rückgabe',
                    'route' => 'pos.terminal',
                    'active' => 'pos.terminal*',
                    'permission' => 'circulation.manage',
                    'order' => 15,
                ],
                [
                    'label' => 'Ausweise ausgeben',
                    'route' => 'pos.labels.cards.issue',
                    'active' => 'pos.labels.cards.issue*',
                    'permission' => 'circulation.manage',
                    'order' => 18,
                ],
                [
                    'label' => 'Vormerkungen',
                    'route' => 'pos.reservations.index',
                    'active' => 'pos.reservations.*',
                    'permission' => 'circulation.manage',
                    'order' => 25,
                ],
                [
                    'label' => 'Buchwünsche',
                    'route' => 'pos.wishes.index',
                    'active' => 'pos.wishes.*',
                    'permission' => 'wishes.manage',
                    'order' => 28,
                ],
                [
                    'label' => 'Medium erfassen',
                    'route' => 'pos.catalog.intake.identify',
                    'active' => 'pos.catalog.intake.*',
                    'permission' => 'catalog.manage',
                    'order' => 30,
                ],
                [
                    'label' => 'Medien einsortieren',
                    'route' => 'pos.shelving',
                    'active' => 'pos.shelving*',
                    'permission' => 'circulation.manage',
                    'order' => 32,
                ],
                [
                    'label' => 'Ausleihkonten',
                    'route' => 'pos.patrons.index',
                    'active' => 'pos.patrons.*',
                    'permission' => 'patrons.lookup',
                    'order' => 40,
                ],
                [
                    'label' => 'Alle Vorgänge',
                    'route' => 'pos.processes',
                    'active' => 'pos.processes',
                    'permission' => 'surface.pos.access',
                    'order' => 80,
                ],
                [
                    'label' => 'Hilfe',
                    'route' => 'pos.help',
                    'active' => 'pos.help*',
                    'permission' => 'surface.pos.access',
                    'order' => 90,
                ],
            ],
        ],
        'administration' => [
            'label' => 'Verwaltung',
            'items' => [
                [
                    'label' => 'Übersicht',
                    'route' => 'administration.home',
                    'preview_route' => 'preview.administration',
                    'active' => 'administration.home',
                    'permission' => 'surface.administration.access',
                    'order' => 10,
                ],
                [
                    'label' => 'Schule',
                    'route' => 'administration.school.index',
                    'active' => 'administration.school.*',
                    'permission' => 'school.manage',
                    'order' => 20,
                ],
                [
                    'label' => 'Seiten',
                    'route' => 'administration.pages.index',
                    'active' => 'administration.pages.*',
                    'permission' => 'content.manage',
                    'order' => 35,
                ],
                [
                    'label' => 'Benutzerkonten',
                    'route' => 'administration.users.index',
                    'active' => 'administration.users.*',
                    'permission' => 'users.manage',
                    'order' => 25,
                ],
                [
                    'label' => 'Systemzustand',
                    'route' => 'administration.system.index',
                    'active' => 'administration.system.*',
                    'permission' => 'system.view',
                    'order' => 38,
                ],
                [
                    'label' => 'Protokoll',
                    'route' => 'administration.audit.index',
                    'active' => 'administration.audit.*',
                    'permission' => 'audit.view',
                    'order' => 40,
                ],
                [
                    'label' => 'Regalbretter',
                    'route' => 'administration.shelves.index',
                    'active' => 'administration.shelves.*',
                    'permission' => 'shelves.manage',
                    'order' => 32,
                ],
                [
                    'label' => 'Signaturen und Themen',
                    'route' => 'administration.signatures.index',
                    'active' => 'administration.signatures.*',
                    'permission' => 'shelves.manage',
                    'order' => 31,
                ],
                [
                    'label' => 'Inventarnummern',
                    'route' => 'administration.inventory.index',
                    'active' => 'administration.inventory.*',
                    'permission' => 'inventory.renumber',
                    'order' => 33,
                ],
                [
                    'label' => 'Öffnungszeiten',
                    'route' => 'administration.calendar.index',
                    'active' => 'administration.calendar.*',
                    'permission' => 'school.manage',
                    'order' => 30,
                ],
            ],
        ],
    ],
];
