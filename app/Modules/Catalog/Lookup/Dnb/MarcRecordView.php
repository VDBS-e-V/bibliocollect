<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Lookup\Dnb;

use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Lesezugriff auf einen einzelnen MARC21-Slim-Datensatz.
 */
final readonly class MarcRecordView
{
    public function __construct(
        private DOMXPath $xpath,
        private DOMNode $record,
    ) {}

    public function leader(): string
    {
        return $this->text('m:leader') ?? '';
    }

    public function control(string $tag): ?string
    {
        return $this->text("m:controlfield[@tag='{$tag}']");
    }

    /** @return list<DOMElement> */
    public function fields(string $tag): array
    {
        $nodes = $this->xpath->query("m:datafield[@tag='{$tag}']", $this->record);
        $fields = [];

        if ($nodes === false) {
            return $fields;
        }

        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $fields[] = $node;
            }
        }

        return $fields;
    }

    /** @return list<string> */
    public function subfields(DOMElement $field, string $code): array
    {
        $nodes = $this->xpath->query("m:subfield[@code='{$code}']", $field);
        $values = [];

        if ($nodes === false) {
            return $values;
        }

        foreach ($nodes as $node) {
            $value = trim($node->textContent);

            if ($value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }

    public function subfield(DOMElement $field, string $code): ?string
    {
        return $this->subfields($field, $code)[0] ?? null;
    }

    private function text(string $path): ?string
    {
        $nodes = $this->xpath->query($path, $this->record);

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $value = trim((string) $nodes->item(0)?->textContent);

        return $value === '' ? null : $value;
    }
}
