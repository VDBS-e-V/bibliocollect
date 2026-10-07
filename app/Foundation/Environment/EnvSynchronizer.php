<?php

declare(strict_types=1);

namespace App\Foundation\Environment;

/**
 * Gleicht Environment-Dateien mit der Vorlage (.env.example) ab. Rein auf Text, ohne Dateizugriff.
 *
 * Die Vorlage bestimmt Reihenfolge und Kommentare. Vorhandene Zeilen des Ziels bleiben unverändert, solange nicht erzwungen wird.
 * Ergebnisse nennen nur Schlüsselnamen, nie Werte.
 */
final class EnvSynchronizer
{
    private const KEY_LINE = '/^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_.]*)\s*=/';

    /**
     * Schlüssel mit der Zeile, in der sie stehen (die erste gilt).
     *
     * @return array<string, string>
     */
    public function parse(string $content): array
    {
        $entries = [];

        foreach ($this->lines($content) as $line) {
            $key = $this->keyOf($line);

            if ($key !== null && ! array_key_exists($key, $entries)) {
                $entries[$key] = $line;
            }
        }

        return $entries;
    }

    /**
     * Schlüssel mit ihrem Wert (ohne umgebende Anführungszeichen und ohne Zeilenkommentar).
     *
     * @return array<string, string>
     */
    public function values(string $content): array
    {
        return array_map(fn (string $line): string => $this->valueOf($line), $this->parse($content));
    }

    /** @return array{missing: list<string>, extra: list<string>} */
    public function diff(string $template, string $target): array
    {
        $templateKeys = array_keys($this->parse($template));
        $targetKeys = array_keys($this->parse($target));

        return [
            'missing' => array_values(array_diff($templateKeys, $targetKeys)),
            'extra' => array_values(array_diff($targetKeys, $templateKeys)),
        ];
    }

    /**
     * missing: aus der Vorlage ergänzt. extra: nur im Ziel (bei $prune entfernt, sonst ans Ende gestellt).
     * changed: bestehende Schlüssel, deren Wert durch $force ersetzt wurde.
     *
     * @return array{content: string, missing: list<string>, extra: list<string>, changed: list<string>}
     */
    public function synchronize(string $template, string $target, bool $force = false, bool $prune = false): array
    {
        $existing = $this->parse($target);
        $templateEntries = $this->parse($template);
        $missing = [];
        $changed = [];
        $output = [];
        $handled = [];

        foreach ($this->lines($template) as $line) {
            $key = $this->keyOf($line);

            if ($key === null) {
                $output[] = $line;

                continue;
            }

            // Doppelte Schlüssel in der Vorlage nur einmal übernehmen.
            if (isset($handled[$key])) {
                continue;
            }

            $handled[$key] = true;

            if (! array_key_exists($key, $existing)) {
                $missing[] = $key;
                $output[] = $line;

                continue;
            }

            if ($force) {
                if ($this->valueOf($existing[$key]) !== $this->valueOf($templateEntries[$key])) {
                    $changed[] = $key;
                }

                $output[] = $line;

                continue;
            }

            $output[] = $existing[$key];
        }

        $extra = array_values(array_diff(array_keys($existing), array_keys($templateEntries)));

        if ($extra !== [] && ! $prune) {
            while ($output !== [] && trim(end($output)) === '') {
                array_pop($output);
            }

            $output[] = '';
            $output[] = '# Weitere Einträge (nicht in der Vorlage)';

            foreach ($extra as $key) {
                $output[] = $existing[$key];
            }
        }

        while ($output !== [] && trim(end($output)) === '') {
            array_pop($output);
        }

        return [
            'content' => $output === [] ? '' : implode("\n", $output)."\n",
            'missing' => $missing,
            'extra' => $extra,
            'changed' => $changed,
        ];
    }

    /** @return list<string> */
    private function lines(string $content): array
    {
        $content = ltrim($content, "\xEF\xBB\xBF");

        if ($content === '') {
            return [];
        }

        return preg_split('/\r\n|\n|\r/', rtrim($content, "\r\n")) ?: [];
    }

    private function keyOf(string $line): ?string
    {
        $trimmed = ltrim($line);

        if ($trimmed === '' || $trimmed[0] === '#') {
            return null;
        }

        return preg_match(self::KEY_LINE, $line, $matches) === 1 ? $matches[1] : null;
    }

    private function valueOf(string $line): string
    {
        $value = ltrim((string) substr($line, (int) strpos($line, '=') + 1));

        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $quote = $value[0];
            $end = strpos($value, $quote, 1);

            return $end === false ? substr($value, 1) : substr($value, 1, $end - 1);
        }

        $comment = preg_match('/\s#/', $value, $match, PREG_OFFSET_CAPTURE) === 1 ? $match[0][1] : null;

        return trim($comment === null ? $value : substr($value, 0, $comment));
    }
}
