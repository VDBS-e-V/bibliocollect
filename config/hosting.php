<?php

declare(strict_types=1);

return [
    // Einrichtungsseite unter /_setup für Hosting ohne SSH (Migrationen ausführen, erstes Verwaltungskonto anlegen).
    // Ist der Wert leer oder kürzer als 24 Zeichen, gibt es die Seite nicht. Nach der Einrichtung wieder leeren.
    'setup_token' => env('SETUP_TOKEN'),

    // Web-Cron unter /_cron/{token} für Anbieter, die nur eine URL regelmäßig aufrufen können.
    // Ist der Wert leer oder kürzer als 24 Zeichen, gibt es die Adresse nicht.
    'cron_token' => env('CRON_TOKEN'),

    // Betriebsüberwachung: Adresse für Meldungen über Fehler, ausgefallenen Cron und fehlgeschlagene Jobs.
    // Ohne Adresse landen die Meldungen nur im Log und auf der Seite „Systemzustand“.
    'alert_email' => env('ALERT_EMAIL'),
    'alert_throttle_minutes' => (int) env('ALERT_THROTTLE_MINUTES', 30),

    // Ab so vielen Minuten ohne Cron-Lauf gilt der Cron als ausgefallen.
    'cron_gap_minutes' => (int) env('CRON_GAP_MINUTES', 15),

    // Schlüssel für die Statusadresse /_status (JSON für ein Monitoring). Ohne Wert gilt der CRON_TOKEN.
    'status_token' => env('STATUS_TOKEN'),
];
