<?php

declare(strict_types=1);

namespace App\Modules\Content\Services;

/**
 * Macht aus dem gespeicherten Seitentext sichere Ausgabe. Seit dem Texteditor ist das gespeicherte Format HTML. Texte im alten
 * Format (einfacher Text mit „## Überschrift“ und „- Liste“) werden dabei erkannt und über {@see PageTextRenderer} umgewandelt.
 */
final readonly class PageContentRenderer
{
    public function __construct(private RichTextSanitizer $sanitizer, private PageTextRenderer $legacy) {}

    /** Sicheres HTML für Anzeige und Editor. */
    public function toHtml(string $body): string
    {
        $html = $this->looksLikeHtml($body) ? $body : $this->legacy->render($body);

        return $this->sanitizer->sanitize($html);
    }

    /** Ob der Text schon HTML mit Blockelementen ist (sonst gilt er als einfacher Text im alten Format). */
    public function looksLikeHtml(string $body): bool
    {
        return preg_match('~<(p|h[2-4]|ul|ol|table|blockquote|hr)\b~i', $body) === 1;
    }
}
