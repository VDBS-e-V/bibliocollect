<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Contracts;

use App\Modules\Patrons\Models\Patron;

/**
 * Andere Module melden Gründe, aus denen ein Ausleihkonto noch nicht ausscheiden darf (z. B. offene Ausleihen).
 * Implementierungen werden mit dem Tag `patron.departure_guards` registriert; Patrons kennt sie nicht.
 */
interface PatronDepartureGuard
{
    /** @return list<string> */
    public function blockReasons(Patron $patron): array;
}
