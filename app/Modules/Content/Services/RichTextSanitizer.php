<?php

declare(strict_types=1);

namespace App\Modules\Content\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Reduziert HTML aus dem Texteditor auf eine feste Erlaubnisliste. Alles andere (Skripte, Stile, Ereignis-Attribute, fremde Elemente,
 * Links mit anderen Schemata als http, https, mailto und tel) fällt weg. Wird beim Speichern und noch einmal beim Ausgeben angewendet.
 */
final class RichTextSanitizer
{
    /** Elemente, die der Editor erzeugen darf. */
    private const ELEMENTS = [
        'h2', 'h3', 'h4', 'p', 'br', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li', 'blockquote', 'hr',
        'table', 'caption', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    private ?HtmlSanitizer $sanitizer = null;

    public function sanitize(string $html): string
    {
        $this->sanitizer ??= new HtmlSanitizer($this->config());

        return trim($this->sanitizer->sanitizeFor('body', $html));
    }

    /** Ob nach dem Bereinigen sichtbarer Text übrig bleibt (reine Leerabsätze zählen nicht). */
    public function hasText(string $html): bool
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/[\s\x{00A0}]+/u', '', $text) !== '';
    }

    private function config(): HtmlSanitizerConfig
    {
        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowRelativeMedias(false)
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->dropElement('script')
            ->dropElement('style');

        foreach (self::ELEMENTS as $element) {
            $config = $config->allowElement($element);
        }

        return $config
            ->allowElement('a', ['href', 'title'])
            ->allowAttribute('scope', ['th'])
            ->allowAttribute('colspan', ['th', 'td'])
            ->allowAttribute('rowspan', ['th', 'td']);
    }
}
