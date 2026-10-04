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
                    'active' => 'public.*',
                    'order' => 10,
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
                    'label' => 'Ausleihkonten',
                    'route' => 'pos.patrons.index',
                    'active' => 'pos.patrons.*',
                    'permission' => 'patrons.lookup',
                    'order' => 20,
                ],
                [
                    'label' => 'Katalogpflege',
                    'route' => 'pos.catalog.index',
                    'active' => 'pos.catalog.*',
                    'permission' => 'catalog.manage',
                    'order' => 30,
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
            ],
        ],
    ],
];
