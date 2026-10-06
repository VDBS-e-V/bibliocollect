<?php

declare(strict_types=1);

return [
    'default_loan_period_days' => 14,

    // Verlängerungen: Höchstzahl je Ausleihe, Dauer je Verlängerung (null = wie die Leihfrist)
    // und ob bereits überfällige Ausleihen noch verlängert werden dürfen.
    'max_renewals' => 2,
    'renewal_period_days' => null,
    'allow_overdue_renewal' => false,

    // Vormerkungen: Tage, die ein bereitgelegtes Exemplar abholbereit bleibt, und Höchstzahl offener Vormerkungen je Ausleihkonto.
    'reservation_pickup_days' => 7,
    'max_open_reservations' => 5,
];
