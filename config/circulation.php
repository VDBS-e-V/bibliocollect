<?php

declare(strict_types=1);

return [
    // Leihfrist in Tagen: Standard, abweichend je Art des Ausleihkontos (student, teacher, employee) und je Medientyp
    // (z. B. 'audiobook' => 7). Der Medientyp hat Vorrang.
    'default_loan_period_days' => 14,
    'loan_periods' => [
        'by_kind' => ['teacher' => 28, 'employee' => 28],
        'by_media_type' => [],
    ],

    // Höchstzahl gleichzeitig ausgeliehener Medien je Ausleihkonto (0 = unbegrenzt).
    'max_open_loans' => [
        'default' => 5,
        'by_kind' => ['teacher' => 20, 'employee' => 20],
    ],

    // Verlängerungen: Höchstzahl je Ausleihe, Dauer je Verlängerung (null = wie die Leihfrist)
    // und ob bereits überfällige Ausleihen noch verlängert werden dürfen.
    'max_renewals' => 2,
    'renewal_period_days' => null,
    'allow_overdue_renewal' => false,

    // Vormerkungen: Tage, die ein bereitgelegtes Exemplar abholbereit bleibt, und Höchstzahl offener Vormerkungen je Ausleihkonto.
    'reservation_pickup_days' => 7,
    'max_open_reservations' => 5,

    // Puffer in Tagen für die Wartezeit einer Vormerkung: Leihfrist + eine Verlängerung + dieser Puffer.
    'reservation_buffer_days' => 7,

    // Wie viele Vormerkungen je aktivem Exemplar eines Titels angenommen werden (1 = die Warteschlange ist nie länger als die Zahl der Exemplare).
    'max_reservations_per_copy' => 1,

    // Buchwünsche: Höchstzahl offener Wünsche je Ausleihkonto.
    'max_open_wishes' => 3,
];
