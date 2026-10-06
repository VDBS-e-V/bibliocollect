<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use InvalidArgumentException;

/**
 * Erzeugt einen Code-128-Strichcode (Zeichensatz B, druckbares ASCII) als SVG. Ohne Abhängigkeiten, damit er auch im
 * Release-Paket und auf einfachem Webspace funktioniert. Scanner lesen ihn wie jeden Code 128.
 */
final class Code128Svg
{
    /** Strich-/Lückenbreiten je Symbol (Wert 0 bis 106); 106 ist das Stoppzeichen mit 7 Elementen. */
    private const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    private const START_B = 104;

    private const STOP = 106;

    /** Anzahl der Module (kleinste Strichbreiten) des Codes inklusive Ruhezone. */
    public static function modules(string $text): int
    {
        return (11 * (count(self::symbols($text)) - 1)) + 13 + 20;
    }

    /** @return list<int> Symbolwerte: Startzeichen, Daten, Prüfzeichen, Stoppzeichen */
    public static function symbols(string $text): array
    {
        if ($text === '' || preg_match('/^[\x20-\x7E]+$/', $text) !== 1) {
            throw new InvalidArgumentException('Ein Strichcode kann nur druckbare ASCII-Zeichen enthalten.');
        }

        $values = [self::START_B];
        $sum = self::START_B;

        foreach (str_split($text) as $index => $character) {
            $value = ord($character) - 32;
            $values[] = $value;
            $sum += $value * ($index + 1);
        }

        $values[] = $sum % 103;
        $values[] = self::STOP;

        return $values;
    }

    /** SVG mit Ruhezone von 10 Modulen links und rechts; die Breite passt sich dem umgebenden Element an. */
    public static function render(string $text, int $height = 40): string
    {
        $x = 10;
        $bars = '';

        foreach (self::symbols($text) as $symbol) {
            $isBar = true;

            foreach (str_split(self::PATTERNS[$symbol]) as $width) {
                $w = (int) $width;

                if ($isBar) {
                    $bars .= '<rect x="'.$x.'" y="0" width="'.$w.'" height="'.$height.'"/>';
                }

                $x += $w;
                $isBar = ! $isBar;
            }
        }

        $total = $x + 10;
        $label = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$total.' '.$height.'" preserveAspectRatio="none" role="img" aria-label="Strichcode '.$label.'" shape-rendering="crispEdges" fill="#000">'
            .'<title>Strichcode '.$label.'</title>'.$bars.'</svg>';
    }
}
