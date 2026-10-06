<?php

declare(strict_types=1);

return [
    // Einrichtungsseite unter /_setup für Hosting ohne SSH (Migrationen ausführen, erstes Verwaltungskonto anlegen).
    // Ist der Wert leer oder kürzer als 24 Zeichen, gibt es die Seite nicht. Nach der Einrichtung wieder leeren.
    'setup_token' => env('SETUP_TOKEN'),

    // Web-Cron unter /_cron/{token} für Anbieter, die nur eine URL regelmäßig aufrufen können.
    // Ist der Wert leer oder kürzer als 24 Zeichen, gibt es die Adresse nicht.
    'cron_token' => env('CRON_TOKEN'),
];
