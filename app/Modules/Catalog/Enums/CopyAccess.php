<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

/**
 * Zugänglichkeit eines Exemplars. Die Werte stehen im Feld `access_status`; „frei“ ist der Wert des Altsystems.
 */
enum CopyAccess: string
{
    case Free = 'frei';
    case LibraryOnly = 'nur_bibliothek';
    case OnRequest = 'nur_nachfrage';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Frei zugänglich',
            self::LibraryOnly => 'Nur Nutzung in der Bibliothek',
            self::OnRequest => 'Nur auf Nachfrage (verschlossen)',
        };
    }

    /** Nicht gesetzte oder unbekannte Werte gelten als frei zugänglich. */
    public static function fromStored(?string $value): self
    {
        return $value === null ? self::Free : (self::tryFrom($value) ?? self::Free);
    }

    /** Text für die Anzeige; null, wenn nichts Besonderes zu sagen ist (frei zugänglich). */
    public static function noteFor(?string $value): ?string
    {
        if ($value === null || $value === '' || $value === self::Free->value) {
            return null;
        }

        return self::tryFrom($value)?->label() ?? $value;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
