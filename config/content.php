<?php

declare(strict_types=1);

return [
    /*
     * Lizenzschlüssel des Texteditors (TinyMCE, selbst gehostet). „gpl“ nutzt die Open-Source-Lizenz GPL-2.0-or-later;
     * mit einer gekauften Lizenz steht hier der Schlüssel (TINYMCE_LICENSE_KEY in der .env).
     */
    'editor_license_key' => env('TINYMCE_LICENSE_KEY', 'gpl'),

    /*
     * Textbausteine: feste Stellen, an denen die Verwaltung einen kurzen Hinweis einblenden kann (Verwaltung → Textbausteine).
     * Ein neuer Eintrag hier plus `<x-content.block key="…" />` an der Stelle genügt.
     */
    'blocks' => [
        'home.notice' => ['title' => 'Hinweis auf der Startseite', 'place' => 'Oben auf der Startseite, zum Beispiel Ferienöffnungszeiten oder eine Störung.'],
        'catalog.notice' => ['title' => 'Hinweis im Katalog', 'place' => 'Über der Trefferliste im öffentlichen Katalog, zum Beispiel „Inventur: Ausleihe bis Freitag eingeschränkt“.'],
    ],
];
