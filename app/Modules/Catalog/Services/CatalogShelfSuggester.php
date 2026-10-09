<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\Edition;

/**
 * Schlägt Regalbretter anhand der Angaben zum Buch vor: Schlagwörter, Titel, Reihe, Thema, Zielgruppe, Inhaltsangabe und Alter werden
 * mit dem verglichen, was das Regalbrett ausmacht (Beschriftung, Themenbereiche samt Hauptbereich und Beschreibung, Altersangaben
 * wie „Kl. 2–3“ oder „ab 10“). Der Vorschlag ist eine Hilfe zum Bestätigen, keine Entscheidung.
 */
final class CatalogShelfSuggester
{
    /** Mindestpunktzahl, ab der ein Brett vorgeschlagen wird. */
    private const THRESHOLD = 4;

    private const STOPWORDS = ['aber', 'alle', 'auch', 'dass', 'dein', 'deine', 'diese', 'dieser', 'durch', 'eine', 'einen', 'einer', 'eines', 'gibt', 'haben', 'ihre', 'immer', 'kann', 'können', 'mehr', 'mein', 'nach', 'noch', 'oder', 'sein', 'seine', 'sich', 'sind', 'viele', 'wenn', 'werden', 'wird', 'wieder', 'zwei', 'ihrer', 'ihrem', 'ihren', 'dieses', 'damit', 'geschichte', 'buch', 'bücher', 'kinder', 'kind'];

    /**
     * @return list<array{code: string, display: string, score: int, reasons: list<string>}> die besten Regalbretter, bestes zuerst
     */
    public function suggest(Edition $edition, int $limit = 3): array
    {
        $book = $this->bookTerms($edition);

        if ($book === []) {
            return [];
        }

        $age = $this->bookAge($edition);
        $ranked = [];

        foreach (CatalogShelf::query()->where('is_active', true)->with(['topics.parent'])->orderBy('sort_order')->orderBy('code')->get() as $shelf) {
            $score = 0;
            $reasons = [];

            foreach ($this->shelfTerms($shelf) as $stem => $info) {
                if (! isset($book[$stem])) {
                    continue;
                }

                $score += $book[$stem]['weight'] * $info['weight'];
                $reasons[$info['word']] = true;
            }

            if ($score > 0 && $age !== null) {
                $score += $this->ageBonus($shelf, $age);
            }

            if ($score >= self::THRESHOLD) {
                $ranked[] = ['code' => $shelf->code, 'display' => $shelf->display(), 'score' => $score, 'reasons' => array_slice(array_keys($reasons), 0, 4)];
            }
        }

        usort($ranked, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_slice($ranked, 0, $limit);
    }

    /** @return array<string, array{weight: int, word: string}> Stammform zu Gewicht und Wort */
    private function bookTerms(Edition $edition): array
    {
        $edition->loadMissing('title');

        $sources = [
            [3, (string) $edition->subject_keywords],
            [2, (string) $edition->subject_keywords_system],
            [3, (string) $edition->title?->preferred_title.' '.(string) $edition->title?->subtitle],
            [2, (string) $edition->series_statement],
            [3, (string) $edition->local_classification],
            [2, (string) $edition->target_audience],
            [1, mb_substr(strip_tags((string) $edition->summary), 0, 800)],
        ];

        $terms = [];

        foreach ($sources as [$weight, $text]) {
            foreach ($this->words($text) as $word) {
                $stem = $this->stem($word);

                if (! isset($terms[$stem]) || $terms[$stem]['weight'] < $weight) {
                    $terms[$stem] = ['weight' => $weight, 'word' => $word];
                }
            }
        }

        return $terms;
    }

    /** @return array<string, array{weight: int, word: string}> */
    private function shelfTerms(CatalogShelf $shelf): array
    {
        $sources = [[2, (string) $shelf->label]];

        foreach ($shelf->topics as $topic) {
            $sources[] = [2, $topic->name];
            $sources[] = [1, (string) $topic->description];

            if ($topic->parent !== null) {
                $sources[] = [1, $topic->parent->name];
            }
        }

        $terms = [];

        foreach ($sources as [$weight, $text]) {
            foreach ($this->words($text) as $word) {
                $stem = $this->stem($word);

                if (! isset($terms[$stem]) || $terms[$stem]['weight'] < $weight) {
                    $terms[$stem] = ['weight' => $weight, 'word' => $word];
                }
            }
        }

        return $terms;
    }

    /** @return list<string> */
    private function words(string $text): array
    {
        $words = preg_split('/[^\p{L}]+/u', mb_strtolower($text)) ?: [];

        return array_values(array_unique(array_filter($words, static fn (string $word): bool => mb_strlen($word) >= 4 && ! in_array($word, self::STOPWORDS, true))));
    }

    /** Grobe Stammform, damit „Tiere“ und „Tier“ oder „Abenteuer“ und „Abenteuern“ zusammenfinden. */
    private function stem(string $word): string
    {
        foreach (['ern', 'en', 'er', 'es', 'e', 'n', 's'] as $suffix) {
            if (mb_strlen($word) > 5 && str_ends_with($word, $suffix)) {
                $word = mb_substr($word, 0, -mb_strlen($suffix));

                break;
            }
        }

        return mb_substr($word, 0, 7);
    }

    /** Alter, ab dem das Buch gedacht ist (Altersangabe oder FSK-nahe Angabe); null, wenn unbekannt. */
    private function bookAge(Edition $edition): ?int
    {
        if ($edition->minimum_age !== null) {
            return (int) $edition->minimum_age;
        }

        // Die Altersempfehlung ist als JSON gespeichert (zum Beispiel {"raw": "ab 8 Jahren"}); für die Suche zählt der Text.
        $recommendation = [];
        $stored = (array) $edition->age_recommendation;
        array_walk_recursive($stored, static function (mixed $value) use (&$recommendation): void {
            if (is_scalar($value)) {
                $recommendation[] = (string) $value;
            }
        });

        foreach ([implode(' ', $recommendation), (string) $edition->target_audience] as $text) {
            if (preg_match('/(?:ab\s+)?(\d{1,2})\s*(?:\+|jahr|[–-]\s*\d{1,2})/iu', $text, $m) === 1) {
                return (int) $m[1];
            }
        }

        return null;
    }

    /** Alter im Regalbrett-Namen: „(ab 10/11)“ oder „(Kl. 2–3)“ (Klasse n entspricht etwa n + 5 Jahren). */
    private function ageBonus(CatalogShelf $shelf, int $age): int
    {
        $label = (string) $shelf->label;

        if (preg_match('/ab\s+(\d{1,2})/iu', $label, $m) === 1) {
            return $age >= (int) $m[1] ? 2 : -3;
        }

        if (preg_match('/Kl\.\s*(\d{1,2})(?:\s*[–-]\s*(\d{1,2}))?/u', $label, $m) === 1) {
            $from = (int) $m[1] + 5;
            $to = (int) ($m[2] ?? $m[1]) + 6;

            return $age >= $from - 1 && $age <= $to + 1 ? 2 : -2;
        }

        return 0;
    }
}
