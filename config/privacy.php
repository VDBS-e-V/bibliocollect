<?php

declare(strict_types=1);

return [
    // Nach so vielen Jahren werden abgeschlossene Vorgänge anonymisiert: Rückgabe bei Ausleihen, Abschluss bei Vormerkungen,
    // Austritt bei Ausleihkonten, Ereigniszeitpunkt beim Protokoll.
    'retention_years' => 3,
];
