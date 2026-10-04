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
            ],
        ],
        'administration' => [
            'label' => 'Verwaltung',
            'items' => [
                [
                    'label' => 'Übersicht',
                    'route' => 'administration.home',
                    'preview_route' => 'preview.administration',
                    'active' => 'administration.*',
                    'permission' => 'surface.administration.access',
                    'order' => 10,
                ],
            ],
        ],
    ],
];
