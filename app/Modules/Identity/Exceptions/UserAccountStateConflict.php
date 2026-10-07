<?php

declare(strict_types=1);

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class UserAccountStateConflict extends RuntimeException
{
    public static function lastManager(): self
    {
        return new self('Das ist das letzte aktive Konto, das Benutzerkonten verwalten darf. Ohne dieses Recht könnte niemand mehr Konten verwalten. Bitte zuerst einem anderen Konto die Rolle „Verwaltung“ geben.');
    }

    public static function ownAccount(): self
    {
        return new self('Das eigene Konto kann man nicht selbst deaktivieren.');
    }

    public static function emailTaken(): self
    {
        return new self('Zu dieser E-Mail-Adresse gibt es schon ein Konto.');
    }
}
