<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Exceptions;

use RuntimeException;

/** Eine Position des Vorgangs ließ sich beim Bestätigen nicht buchen; der ganze Vorgang wurde zurückgenommen. */
final class CounterTransactionFailed extends RuntimeException
{
    public static function atPosition(int $position, string $title, string $reason): self
    {
        return new self("Position {$position} („{$title}“): {$reason} Es wurde nichts gebucht.");
    }

    public static function empty(): self
    {
        return new self('Der Vorgang ist leer.');
    }
}
