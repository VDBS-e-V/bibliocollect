<?php

declare(strict_types=1);

namespace App\Modules\Content\Services;

/**
 * Macht aus einfachem Text sicheres HTML: Absätze, Überschriften (`## Titel`), Listen (`- Punkt`), Adressen und
 * E-Mail-Adressen werden zu Links. Eingegebenes HTML wird nie ausgeführt, alles wird zuerst maskiert.
 */
final class PageTextRenderer
{
    public function render(string $text): string
    {
        $blocks = preg_split('/\R{2,}/', trim(str_replace("\r\n", "\n", $text))) ?: [];
        $html = [];

        foreach ($blocks as $block) {
            $lines = array_values(array_filter(array_map('rtrim', explode("\n", $block)), static fn (string $line): bool => $line !== ''));

            if ($lines === []) {
                continue;
            }

            if (str_starts_with($lines[0], '## ')) {
                $html[] = '<h2>'.$this->inline(substr($lines[0], 3)).'</h2>';
                array_shift($lines);

                if ($lines === []) {
                    continue;
                }
            }

            if (array_reduce($lines, static fn (bool $all, string $line): bool => $all && str_starts_with($line, '- '), true)) {
                $html[] = '<ul>'.implode('', array_map(fn (string $line): string => '<li>'.$this->inline(substr($line, 2)).'</li>', $lines)).'</ul>';

                continue;
            }

            $html[] = '<p>'.implode('<br>', array_map(fn (string $line): string => $this->inline($line), $lines)).'</p>';
        }

        return implode("\n", $html);
    }

    private function inline(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $escaped = preg_replace_callback('~(https?://[^\s<]+[^\s<.,;:!?)])~u', static fn (array $match): string => '<a href="'.$match[1].'" rel="noopener nofollow">'.$match[1].'</a>', $escaped) ?? $escaped;

        return preg_replace('~(?<![">/\w.-])([\w.+-]+@[\w-]+(?:\.[\w-]+)+)~u', '<a href="mailto:$1">$1</a>', $escaped) ?? $escaped;
    }
}
