<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Title;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Erzeugt ein typografisches SVG einmalig auf demselben Cover-Datenträger wie Originalcover.
 * Dateinamen sind inhaltsabhängig (Titel, Autor:innen, Version), damit Änderungen eine neue
 * Datei erhalten und Browser ihre bisherigen Bilder sicher zwischenspeichern können.
 */
final class CatalogGeneratedCoverService
{
    private const VERSION = 'v1';

    /** @var list<array{background: string, arc: string, inside: string, foreground: string}> */
    private const PALETTES = [
        ['background' => '#58275A', 'arc' => '#86518B', 'inside' => '#6C3372', 'foreground' => '#FFFFFF'],
        ['background' => '#BBEFD6', 'arc' => '#7BCBB1', 'inside' => '#A4E4CA', 'foreground' => '#0F172A'],
        ['background' => '#1B2937', 'arc' => '#45647A', 'inside' => '#29465B', 'foreground' => '#FFFFFF'],
        ['background' => '#E9DFD2', 'arc' => '#C9BBA9', 'inside' => '#DFD0C0', 'foreground' => '#201D2B'],
    ];

    /**
     * Gibt den relativen Storage-Pfad zurück. Bei fehlendem Schreibzugriff bleibt die
     * bisherige HTML/CSS-Variante als visuelle Rückfallebene verfügbar.
     */
    public function ensureForTitle(Title $title): ?string
    {
        $title->loadMissing('contributions.contributor');

        $name = trim((string) $title->preferred_title);
        $name = $name !== '' ? $name : 'Ohne Titel';
        $author = $title->contributions->pluck('contributor.display_name')->filter()->take(2)->join(', ');
        $key = hash('sha256', self::VERSION."\0".(string) $title->getKey()."\0".$name."\0".$author);
        $directory = trim((string) config('catalog.covers.directory', 'catalog/covers'), '/');
        $path = $directory.'/generated/'.self::VERSION.'/'.$key.'.svg';

        try {
            $disk = Storage::disk((string) config('catalog.covers.disk', 'public'));

            if (! $disk->exists($path)) {
                if (! $disk->put($path, $this->render($name, $author), ['visibility' => 'public'])) {
                    return null;
                }
            }

            return $path;
        } catch (Throwable $exception) {
            Log::warning('Katalog-Ersatzcover konnte nicht gespeichert werden.', [
                'exception' => $exception::class,
            ]);

            return null;
        }
    }

    /**
     * Mehr als zwei Hex-Zeichen vermeiden die offensichtlichen Kollisionen aus der
     * ersten Designversion. Vier Farben bleiben vier Farben: Wiederholungen sind möglich.
     */
    public function paletteIndex(string $title): int
    {
        return hexdec(substr(hash('sha256', mb_strtolower(trim($title))), 0, 8)) % count(self::PALETTES);
    }

    private function render(string $title, string $author): string
    {
        $colors = self::PALETTES[$this->paletteIndex($title)];
        $lines = $this->wrapTitle($title);
        $size = count($lines) >= 5 ? 27 : (count($lines) >= 4 ? 30 : 35);
        $lineHeight = $size + 3;
        $firstBaseline = 380 - (count($lines) - 1) * $lineHeight;
        $titleSvg = '';

        foreach ($lines as $index => $line) {
            $titleSvg .= sprintf(
                '<text x="30" y="%d" font-size="%d" font-weight="900">%s</text>',
                $firstBaseline + $index * $lineHeight,
                $size,
                $this->escape($line),
            );
        }

        $authorSvg = trim($author) !== ''
            ? '<text x="30" y="431" font-size="17" font-weight="600">'.$this->escape(mb_strimwidth($author, 0, 40, '…')).'</text>'
            : '';

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="480" viewBox="0 0 320 480">'
            .'<rect width="320" height="480" rx="10" fill="%s"/>'
            .'<circle cx="161" cy="365" r="227" fill="%s"/>'
            .'<circle cx="189" cy="407" r="163" fill="%s"/>'
            .'<g fill="%s" font-family="Lato,Arial,sans-serif">'
            .'<text x="30" y="37" font-size="10" font-weight="900" letter-spacing="1.6">BIBLIOCOLLECT · VDBS</text>'
            .'%s%s</g></svg>',
            $colors['background'],
            $colors['arc'],
            $colors['inside'],
            $colors['foreground'],
            $titleSvg,
            $authorSvg,
        );
    }

    /** @return list<string> */
    private function wrapTitle(string $title): array
    {
        $words = preg_split('/\s+/u', trim($title)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            while (mb_strlen($word) > 17) {
                if ($current !== '') {
                    $lines[] = $current;
                    $current = '';
                }

                $lines[] = mb_substr($word, 0, 17);
                $word = mb_substr($word, 17);
            }

            if ($word === '') {
                continue;
            }

            $candidate = $current === '' ? $word : $current.' '.$word;

            if (mb_strlen($candidate) > 17 && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        if ($lines === []) {
            return ['Ohne Titel'];
        }

        if (count($lines) > 5) {
            $lines = array_slice($lines, 0, 5);
            $lines[4] = mb_strimwidth($lines[4], 0, 15, '').'…';
        }

        return $lines;
    }

    private function escape(string $value): string
    {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
