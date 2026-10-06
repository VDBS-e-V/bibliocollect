<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

/**
 * Eine einzelne vorgeschlagene Änderung. Übernommen wird später ausschließlich der hier gespeicherte
 * Wert; die prüfende Person wählt nur aus, ob sie ihn übernimmt.
 */
final readonly class MetadataChange
{
    /** Feld ist leer, die Quelle liefert einen Wert. */
    public const FILL = 'fill';

    /** Wert ist beschädigt (z. B. verlorene Umlaute), die Quelle liefert den Ursprung. */
    public const FIX = 'fix';

    /** Lokale Bereinigung ohne externe Quelle (z. B. Steuerzeichen entfernen). */
    public const LOCAL = 'local';

    /** Beide Seiten haben einen Wert, sie unterscheiden sich. Nur zur Ansicht, nie vorausgewählt. */
    public const DIFFERS = 'differs';

    /** Fehlende verantwortliche Person ergänzen. */
    public const ADD = 'add';

    /** Beschädigten Namen einer vorhandenen Person korrigieren. */
    public const RENAME = 'rename';

    /** @param array<string, mixed> $payload zusätzliche Angaben, die die Übernahme braucht (z. B. Rolle, GND-ID) */
    public function __construct(
        public string $key,
        public string $kind,
        public string $label,
        public ?string $current,
        public ?string $proposed,
        public bool $selected,
        public array $payload = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'kind' => $this->kind,
            'label' => $this->label,
            'current' => $this->current,
            'proposed' => $this->proposed,
            'selected' => $this->selected,
            'payload' => $this->payload,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var array<string, mixed> $payload */
        $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];

        return new self(
            key: is_string($data['key'] ?? null) ? $data['key'] : '',
            kind: is_string($data['kind'] ?? null) ? $data['kind'] : self::DIFFERS,
            label: is_string($data['label'] ?? null) ? $data['label'] : '',
            current: is_string($data['current'] ?? null) ? $data['current'] : null,
            proposed: is_string($data['proposed'] ?? null) ? $data['proposed'] : null,
            selected: ($data['selected'] ?? false) === true,
            payload: $payload,
        );
    }

    public function withSelected(bool $selected): self
    {
        return new self($this->key, $this->kind, $this->label, $this->current, $this->proposed, $selected, $this->payload);
    }

    public function isDifference(): bool
    {
        return $this->kind === self::DIFFERS;
    }
}
