<?php

declare(strict_types=1);

return [
    'covers' => [
        'disk' => env('CATALOG_COVER_DISK', 'public'),
        'directory' => env('CATALOG_COVER_DIRECTORY', 'catalog/covers'),
        'max_bytes' => (int) env('CATALOG_COVER_MAX_BYTES', 8 * 1024 * 1024),

        // Quellen werden in dieser Reihenfolge abgefragt; die erste mit Treffer gewinnt.
        'open_library' => [
            'enabled' => (bool) env('CATALOG_COVER_OPEN_LIBRARY', true),
            'timeout' => (int) env('CATALOG_COVER_TIMEOUT', 10),
        ],

        // Google Books verlangt in der Praxis einen (kostenlosen) API-Key. Ohne Key bleibt die Quelle aus.
        'google_books' => [
            'key' => env('CATALOG_COVER_GOOGLE_BOOKS_KEY'),
            'timeout' => (int) env('CATALOG_COVER_TIMEOUT', 10),
        ],
    ],

    // Zusammenfassungen (Klappentexte) werden bei der Erfassung per ISBN nachgeschlagen und nur vorgeschlagen.
    'summaries' => [
        'timeout' => (int) env('CATALOG_SUMMARY_TIMEOUT', 8),
        'open_library' => (bool) env('CATALOG_SUMMARY_OPEN_LIBRARY', true),
    ],

    'lookup' => [
        'dnb' => [
            'endpoint' => env('CATALOG_DNB_SRU_ENDPOINT', 'https://services.dnb.de/sru/dnb'),
            'timeout' => (int) env('CATALOG_DNB_TIMEOUT', 10),
            'max_records' => (int) env('CATALOG_DNB_MAX_RECORDS', 10),
        ],
    ],
];
