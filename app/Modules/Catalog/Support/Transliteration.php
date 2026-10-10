<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Support;

/**
 * Lateinische Umschrift für die Katalogsuche, ohne PHP-Erweiterung „intl“ (die es auf einfachem Webspace nicht immer gibt).
 *
 * Titel und Namen in kyrillischer, griechischer oder arabischer Schrift sowie mit Sonderbuchstaben (zum Beispiel türkisch ı, ş, ğ) bekommen
 * eine einfache Umschrift in Kleinbuchstaben, die in `search_aliases` gespeichert wird. Wer „voyna“ oder „cocuk“ tippt, findet dann auch
 * „Война и мир“ und „Çocuk Kitabı“. Die Schreibung ist bewusst locker: Es gibt viele gebräuchliche Umschriften, deshalb werden Suchwort
 * und gespeicherte Umschrift mit denselben Regeln noch einmal angeglichen ({@see self::loose()}).
 *
 * Chinesisch, Japanisch und Koreanisch haben keine Umschrift (dafür braucht es Wörterbücher); sie werden in Originalschrift gefunden.
 */
final class Transliteration
{
    /** @var array<string, string> */
    private const CYRILLIC = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'ґ' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'є' => 'ye', 'ж' => 'zh', 'з' => 'z',
        'и' => 'i', 'і' => 'i', 'ї' => 'yi', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y',
        'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya', 'ђ' => 'dj', 'ј' => 'j', 'љ' => 'lj', 'њ' => 'nj', 'ћ' => 'c', 'џ' => 'dz', 'ў' => 'u',
    ];

    /** @var array<string, string> */
    private const GREEK = [
        'α' => 'a', 'β' => 'v', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'i', 'θ' => 'th', 'ι' => 'i', 'κ' => 'k', 'λ' => 'l',
        'μ' => 'm', 'ν' => 'n', 'ξ' => 'x', 'ο' => 'o', 'π' => 'p', 'ρ' => 'r', 'σ' => 's', 'ς' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f',
        'χ' => 'ch', 'ψ' => 'ps', 'ω' => 'o', 'ά' => 'a', 'έ' => 'e', 'ή' => 'i', 'ί' => 'i', 'ϊ' => 'i', 'ΐ' => 'i', 'ό' => 'o', 'ύ' => 'y',
        'ϋ' => 'y', 'ΰ' => 'y', 'ώ' => 'o',
    ];

    /** @var array<string, string> Arabisch und Persisch; kurze Vokale stehen nicht in der Schrift. */
    private const ARABIC = [
        'ا' => 'a', 'أ' => 'a', 'إ' => 'i', 'آ' => 'a', 'ٱ' => 'a', 'ب' => 'b', 'پ' => 'p', 'ت' => 't', 'ث' => 'th', 'ج' => 'j', 'چ' => 'ch', 'ح' => 'h',
        'خ' => 'kh', 'د' => 'd', 'ذ' => 'dh', 'ر' => 'r', 'ز' => 'z', 'ژ' => 'zh', 'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'd', 'ط' => 't',
        'ظ' => 'z', 'ع' => '', 'غ' => 'gh', 'ف' => 'f', 'ق' => 'q', 'ك' => 'k', 'ک' => 'k', 'گ' => 'g', 'ل' => 'l', 'م' => 'm', 'ن' => 'n',
        'ه' => 'h', 'ة' => 'a', 'و' => 'w', 'ؤ' => 'w', 'ي' => 'y', 'ی' => 'y', 'ى' => 'a', 'ئ' => 'y', 'ء' => '',
    ];

    /** @var array<string, string> Lateinische Buchstaben mit Akzent oder Sonderform auf den Grundbuchstaben. */
    private const LATIN = [
        'ı' => 'i', 'İ' => 'i', 'ş' => 's', 'ș' => 's', 'ğ' => 'g', 'ç' => 'c', 'ñ' => 'n', 'ł' => 'l', 'ø' => 'o', 'đ' => 'd', 'ð' => 'd', 'þ' => 'th',
        'æ' => 'ae', 'œ' => 'oe', 'ß' => 'ss', 'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ā' => 'a', 'ă' => 'a', 'ą' => 'a',
        'ć' => 'c', 'č' => 'c', 'ď' => 'd', 'ē' => 'e', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'ę' => 'e', 'ě' => 'e', 'ģ' => 'g', 'í' => 'i',
        'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i', 'į' => 'i', 'ķ' => 'k', 'ĺ' => 'l', 'ļ' => 'l', 'ľ' => 'l', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ō' => 'o', 'ő' => 'o', 'ŕ' => 'r', 'ř' => 'r', 'ś' => 's', 'š' => 's', 'ť' => 't',
        'ț' => 't', 'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ū' => 'u', 'ů' => 'u', 'ű' => 'u', 'ų' => 'u', 'ý' => 'y', 'ÿ' => 'y', 'ź' => 'z',
        'ž' => 'z', 'ż' => 'z',
    ];

    /** @var array<string, string> */
    private static array $table = [];

    /**
     * Die Umschrift zu den Texten (Titel, Untertitel, Namen), oder null, wenn sich nichts umschreiben lässt. Bei arabischer Schrift
     * kommt eine zweite Form ohne Vokale dazu, weil die Schrift Vokale auslässt („ktb“ findet „kitab“ und „kutub“).
     */
    public static function aliases(?string ...$texts): ?string
    {
        $parts = [];

        foreach ($texts as $text) {
            if ($text === null || trim($text) === '' || ! self::needs($text)) {
                continue;
            }

            $latin = self::latin($text);

            if ($latin !== '') {
                $parts[$latin] = $latin;

                if (preg_match('/\p{Arabic}/u', $text) === 1) {
                    $skeleton = self::skeleton($latin);
                    $parts[$skeleton] = $skeleton;
                }
            }
        }

        return $parts === [] ? null : mb_substr(implode(' | ', $parts), 0, 1000);
    }

    /** Ob ein Text Zeichen enthält, die eine Umschrift brauchen (nicht lateinische Schrift oder Sonderbuchstaben). */
    public static function needs(string $text): bool
    {
        return preg_match('/[^\x00-\x7F]/u', $text) === 1 && preg_match('/[^\p{Common}\p{Latin}]|[ıİşŞğĞşșłøđðþæœñÇçŁØĐÞ]/u', $text) === 1;
    }

    /** Lateinische Umschrift in Kleinbuchstaben, nur Buchstaben, Ziffern und einfache Leerzeichen. */
    public static function latin(string $text): string
    {
        $text = mb_strtolower(self::stripMarks($text));
        $out = '';

        foreach (mb_str_split($text) as $char) {
            $out .= self::map()[$char] ?? $char;
        }

        return self::loose(trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $out))));
    }

    /**
     * Ein Suchwort für den Abgleich mit der gespeicherten Umschrift: umgeschrieben und angeglichen. Bei Wörtern mit Vokalen kommt die
     * Form ohne Vokale hinzu (nur ab drei Konsonanten, damit kurze Wörter nicht alles treffen).
     *
     * @return list<string>
     */
    public static function searchForms(string $token): array
    {
        $forms = [];
        $latin = self::latin($token);

        if ($latin !== '') {
            $forms[] = $latin;
        }

        $skeleton = self::skeleton($latin);

        if ($skeleton !== '' && $skeleton !== $latin && mb_strlen($skeleton) >= 3) {
            $forms[] = $skeleton;
        }

        return array_values(array_unique($forms));
    }

    /** Gebräuchliche Schreibvarianten angleichen: j und y wie i („Tolstoj“, „Tolstoy“, „Tolstoi“), w wie v („Woina“, „Voina“). */
    private static function loose(string $text): string
    {
        return strtr($text, ['j' => 'i', 'y' => 'i', 'w' => 'v']);
    }

    private static function skeleton(string $latin): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/[aeiou]+/u', '', $latin)));
    }

    /** Arabische Zeichensetzung (Vokalzeichen, Dehnungsstrich) und kombinierende Akzente entfernen. */
    private static function stripMarks(string $text): string
    {
        return (string) preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{0301}\x{0300}\x{0308}]/u', '', $text);
    }

    /** @return array<string, string> */
    private static function map(): array
    {
        return self::$table !== [] ? self::$table : self::$table = array_merge(self::LATIN, self::GREEK, self::CYRILLIC, self::ARABIC);
    }
}
