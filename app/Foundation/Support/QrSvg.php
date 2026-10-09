<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use InvalidArgumentException;

/** Erzeugt einen QR-Code als SVG (reines PHP, ohne Bildbibliothek), schwarz auf weiß, ohne eingebaute Ruhezone. Die Ruhezone setzt der Aufrufer. */
final class QrSvg
{
    public static function render(string $text, string $label = ''): string
    {
        if ($text === '') {
            throw new InvalidArgumentException('Ein QR-Code braucht einen Text.');
        }

        $writer = new Writer(new ImageRenderer(new RendererStyle(100, 0), new SvgImageBackEnd));
        $svg = $writer->writeString($text, 'UTF-8', ErrorCorrectionLevel::M());
        $svg = (string) preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);
        $label = $label !== '' ? $label : 'QR-Code '.$text;

        return (string) preg_replace('/^<svg /', '<svg role="img" aria-label="'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'" preserveAspectRatio="xMidYMid meet" ', $svg, 1);
    }
}
