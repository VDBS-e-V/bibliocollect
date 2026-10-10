<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Support\Transliteration;
use Illuminate\Support\Facades\Cache;

/**
 * Hilfen für die Katalogsuche: „Meintest du …?“ bei Tippfehlern und Vorschläge beim Tippen.
 *
 * Die Wortliste (Wörter aus Titeln und Namen) wird kurz zwischengespeichert. Verglichen wird auf der lateinischen, umlautfreien
 * Schreibung ({@see Transliteration::latin()}), damit „Raeuber“ und „Räuber“ oder „Tolstoj“ und „Tolstoy“ nah beieinander liegen.
 */
final class SearchSuggestionService
{
    private const VOCABULARY_KEY = 'catalog.search.vocabulary';

    private const VOCABULARY_TTL = 1800;

    private const VOCABULARY_LIMIT = 60000;

    /**
     * Eine bessere Schreibung für den Suchbegriff, wenn ein Wort nicht vorkommt, aber ein sehr ähnliches. Sonst null.
     */
    public function didYouMean(string $term): ?string
    {
        $tokens = preg_split('/\s+/u', trim($term), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens === []) {
            return null;
        }

        $vocabulary = $this->vocabulary();
        $changed = false;
        $result = [];

        foreach ($tokens as $token) {
            $fold = Transliteration::latin($token);

            if ($fold === '' || mb_strlen($fold) < 4 || isset($vocabulary[$fold])) {
                $result[] = $token;

                continue;
            }

            $best = $this->closest($fold, $vocabulary);

            if ($best === null) {
                $result[] = $token;

                continue;
            }

            $result[] = $best;
            $changed = true;
        }

        return $changed ? implode(' ', $result) : null;
    }

    /**
     * Titel und Namen, die mit dem eingetippten Anfang beginnen (oder deren Wörter damit beginnen), für die Vorschlagsliste.
     *
     * @return list<string>
     */
    public function complete(string $prefix, int $limit = 8): array
    {
        $prefix = trim(str_replace(['%', '_'], '', $prefix));

        if (mb_strlen($prefix) < 2) {
            return [];
        }

        return Cache::remember('catalog.search.complete.'.md5(mb_strtolower($prefix)).'.'.$limit, 300, function () use ($prefix, $limit): array {
            $present = [CopyStatus::Active->value, CopyStatus::Damaged->value];
            $titles = Title::query()
                ->where(static function ($query) use ($prefix): void {
                    $query->where('preferred_title', 'like', $prefix.'%')->orWhere('preferred_title', 'like', '% '.$prefix.'%');
                })
                ->whereHas('editions.copies', static fn ($copies) => $copies->whereIn('status', $present))
                ->orderBy('preferred_title')
                ->limit($limit)
                ->pluck('preferred_title')
                ->all();

            $names = [];

            if (count($titles) < $limit) {
                $names = Contributor::query()
                    ->where(static function ($query) use ($prefix): void {
                        $query->where('display_name', 'like', $prefix.'%')->orWhere('display_name', 'like', '% '.$prefix.'%');
                    })
                    ->whereHas('contributions.title.editions.copies', static fn ($copies) => $copies->whereIn('status', $present))
                    ->orderBy('display_name')
                    ->limit($limit - count($titles))
                    ->pluck('display_name')
                    ->all();
            }

            return array_values(array_unique(array_map('strval', [...$titles, ...$names])));
        });
    }

    /**
     * @param  array<string, array{word: string, count: int}>  $vocabulary
     */
    private function closest(string $fold, array $vocabulary): ?string
    {
        $length = strlen($fold);
        $allowed = $length >= 8 ? 2 : 1;
        $best = null;
        $bestDistance = $allowed + 1;
        $bestCount = 0;

        foreach ($vocabulary as $rawKey => $entry) {
            $key = (string) $rawKey;

            if (abs(strlen($key) - $length) > $allowed) {
                continue;
            }

            // Nur Wörter mit gleichem Anfangsbuchstaben: Tippfehler am Wortanfang sind selten, und es spart Rechenzeit.
            if ($key[0] !== $fold[0]) {
                continue;
            }

            $distance = levenshtein($fold, $key);

            if ($distance < $bestDistance || ($distance === $bestDistance && $entry['count'] > $bestCount)) {
                $best = $entry['word'];
                $bestDistance = $distance;
                $bestCount = $entry['count'];
            }
        }

        return $bestDistance <= $allowed ? $best : null;
    }

    /** @return array<string, array{word: string, count: int}> umschriebenes Wort => häufigste Schreibung und Anzahl */
    private function vocabulary(): array
    {
        return Cache::remember(self::VOCABULARY_KEY, self::VOCABULARY_TTL, function (): array {
            $words = [];
            $add = static function (?string $text) use (&$words): void {
                foreach (preg_split('/[^\p{L}\p{N}]+/u', (string) $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
                    if (mb_strlen($word) < 4 || count($words) >= self::VOCABULARY_LIMIT) {
                        continue;
                    }

                    $key = Transliteration::latin($word);

                    if ($key === '') {
                        continue;
                    }

                    // Schreibweise samt Großschreibung merken, damit der Vorschlag wie ein Titel aussieht.
                    $words[$key]['variants'][$word] = ($words[$key]['variants'][$word] ?? 0) + 1;
                    $words[$key]['count'] = ($words[$key]['count'] ?? 0) + 1;
                }
            };

            Title::query()->select(['id', 'preferred_title', 'subtitle'])->chunkById(500, function ($titles) use ($add): void {
                foreach ($titles as $title) {
                    $add($title->preferred_title);
                    $add($title->subtitle);
                }
            });
            Contributor::query()->select(['id', 'display_name'])->chunkById(500, function ($contributors) use ($add): void {
                foreach ($contributors as $contributor) {
                    $add($contributor->display_name);
                }
            });

            $vocabulary = [];

            foreach ($words as $key => $entry) {
                arsort($entry['variants']);
                $vocabulary[$key] = ['word' => (string) array_key_first($entry['variants']), 'count' => $entry['count']];
            }

            return $vocabulary;
        });
    }
}
