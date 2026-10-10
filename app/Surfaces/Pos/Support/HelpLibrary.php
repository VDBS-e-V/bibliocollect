<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Support;

use Illuminate\Support\Facades\File;

/**
 * Die Hilfeartikel des Bibliotheksbetriebs: Markdown-Dateien unter `resources/help/artikel` mit einem Kopf (`titel`, `kurz`,
 * `bereich`, `rollen`, `stichworte`). Dazu Suche und Filter nach Bereich und Rolle, ohne Datenbank.
 */
final class HelpLibrary
{
    /** @var array<string, string> */
    public const AREAS = [
        'grundlagen' => 'Grundlagen',
        'ausleihe' => 'Ausleihe am Tresen',
        'konten' => 'Ausleihkonten und Ausweise',
        'katalog' => 'Katalog und Bestand',
        'verwaltung' => 'Verwaltung und Betrieb',
    ];

    /** @var array<string, string> */
    public const ROLES = [
        'ag' => 'Schüler-AG',
        'mitarbeiter' => 'Mitarbeiter:in',
        'verwaltung' => 'Verwaltung',
    ];

    /** Frühere Adressen der drei großen Seiten, die jetzt Bereiche sind. */
    public const LEGACY = ['ausleihe' => 'ausleihe', 'katalog' => 'katalog', 'verwaltung' => 'verwaltung'];

    /** @var array<string, array{slug: string, title: string, summary: string, area: string, roles: list<string>, keywords: list<string>, body: string}>|null */
    private ?array $articles = null;

    /** @return array<string, array{slug: string, title: string, summary: string, area: string, roles: list<string>, keywords: list<string>, body: string}> */
    public function all(): array
    {
        if ($this->articles !== null) {
            return $this->articles;
        }

        $articles = [];

        foreach (File::glob(resource_path('help/artikel/*.md')) ?: [] as $file) {
            $slug = basename($file, '.md');
            $article = $this->parse($slug, (string) file_get_contents($file));

            if ($article !== null) {
                $articles[$slug] = $article;
            }
        }

        uasort($articles, static fn (array $a, array $b): int => [array_search($a['area'], array_keys(self::AREAS), true), $a['title']] <=> [array_search($b['area'], array_keys(self::AREAS), true), $b['title']]);

        return $this->articles = $articles;
    }

    /** @return array{slug: string, title: string, summary: string, area: string, roles: list<string>, keywords: list<string>, body: string}|null */
    public function find(string $slug): ?array
    {
        return preg_match('/^[a-z0-9-]+$/', $slug) === 1 ? ($this->all()[$slug] ?? null) : null;
    }

    /**
     * Artikel zu Suchwörtern, Bereich und Rolle. Ohne Suchwort bleibt die Reihenfolge nach Bereich und Titel; mit Suchwort zählt die
     * Trefferqualität (Titel vor Stichworten vor Kurzbeschreibung vor Text). Alle Wörter müssen vorkommen.
     *
     * @return list<array{article: array{slug: string, title: string, summary: string, area: string, roles: list<string>, keywords: list<string>, body: string}, snippet: string|null}>
     */
    public function search(?string $query, ?string $area, ?string $role): array
    {
        $terms = array_values(array_filter(preg_split('/\s+/u', $this->fold(trim((string) $query))) ?: [], static fn (string $term): bool => $term !== ''));
        $hits = [];

        foreach ($this->all() as $article) {
            if ($area !== null && $area !== '' && $article['area'] !== $area) {
                continue;
            }

            if ($role !== null && $role !== '' && ! in_array($role, $article['roles'], true)) {
                continue;
            }

            if ($terms === []) {
                $hits[] = ['article' => $article, 'score' => 0, 'snippet' => null];

                continue;
            }

            $title = $this->fold($article['title']);
            $keywords = $this->fold(implode(' ', $article['keywords']));
            $summary = $this->fold($article['summary']);
            $body = $this->fold($this->plain($article['body']));
            $score = 0;

            foreach ($terms as $term) {
                $found = 0;
                $found += str_contains($title, $term) ? 10 : 0;
                $found += str_contains($keywords, $term) ? 6 : 0;
                $found += str_contains($summary, $term) ? 4 : 0;
                $found += min(3, substr_count($body, $term));

                if ($found === 0) {
                    continue 2;
                }

                $score += $found;
            }

            $hits[] = ['article' => $article, 'score' => $score, 'snippet' => $this->snippet($article['body'], $terms)];
        }

        if ($terms !== []) {
            usort($hits, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        }

        return array_map(static fn (array $hit): array => ['article' => $hit['article'], 'snippet' => $hit['snippet']], $hits);
    }

    /**
     * Weitere Artikel aus demselben Bereich.
     *
     * @return list<array{slug: string, title: string, summary: string, area: string, roles: list<string>, keywords: list<string>, body: string}>
     */
    public function related(string $slug, int $limit = 4): array
    {
        $current = $this->find($slug);

        if ($current === null) {
            return [];
        }

        return array_slice(array_values(array_filter($this->all(), static fn (array $article): bool => $article['area'] === $current['area'] && $article['slug'] !== $slug)), 0, $limit);
    }

    /** @return array{slug: string, title: string, summary: string, area: string, roles: list<string>, keywords: list<string>, body: string}|null */
    private function parse(string $slug, string $text): ?array
    {
        $text = str_replace("\r\n", "\n", $text);

        if (preg_match('/\A---\n(.*?)\n---\n(.*)\z/s', $text, $match) !== 1) {
            return null;
        }

        $meta = [];

        foreach (explode("\n", $match[1]) as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $meta[trim($key)] = trim($value);
            }
        }

        $title = $meta['titel'] ?? '';
        $area = $meta['bereich'] ?? '';

        if ($title === '' || ! array_key_exists($area, self::AREAS)) {
            return null;
        }

        $split = static fn (string $value): array => array_values(array_filter(array_map('trim', explode(',', $value)), static fn (string $item): bool => $item !== ''));

        return [
            'slug' => $slug,
            'title' => $title,
            'summary' => $meta['kurz'] ?? '',
            'area' => $area,
            'roles' => array_values(array_intersect(array_keys(self::ROLES), $split($meta['rollen'] ?? ''))),
            'keywords' => $split($meta['stichworte'] ?? ''),
            'body' => trim($match[2]),
        ];
    }

    /** Kleinschreibung und Umlaute aufgelöst, damit „ueberfaellig“ und „überfällig“ dasselbe finden. */
    private function fold(string $text): string
    {
        return strtr(mb_strtolower($text), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
    }

    private function plain(string $markdown): string
    {
        $withoutMarkers = (string) preg_replace('/^\s*(?:[-*]|\d+\.)\s+/m', '', $markdown);

        return trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/[#*_`>|\[\]()]+/u', ' ', $withoutMarkers)));
    }

    /**
     * @param  list<string>  $terms
     */
    private function snippet(string $markdown, array $terms): ?string
    {
        $plain = $this->plain($markdown);
        $folded = $this->fold($plain);

        foreach ($terms as $term) {
            $position = mb_strpos($folded, $term);

            if ($position !== false) {
                $start = max(0, $position - 60);
                $text = mb_substr($plain, $start, 170);

                return ($start > 0 ? '… ' : '').$text.(mb_strlen($plain) > $start + 170 ? ' …' : '');
            }
        }

        return null;
    }
}
