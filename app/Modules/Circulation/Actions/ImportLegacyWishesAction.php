<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Actions;

use App\Modules\Circulation\Enums\WishStatus;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Models\BookWish;
use Illuminate\Support\Carbon;
use JsonException;

/**
 * Übernimmt die Buchwünsche aus dem Altsystem (phpMyAdmin-JSON-Export der Tabelle `bookWishes`) einmalig als neue, offene Wünsche ohne Person.
 * Wünsche, die es schon gibt (gleiche ISBN, sonst gleicher Titel und gleiche Autorenangabe), werden übersprungen; ein zweiter Lauf legt nichts doppelt an.
 */
final class ImportLegacyWishesAction
{
    /** @return array{created: int, skipped: int} */
    public function execute(string $path): array
    {
        $rows = $this->rows($path);
        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $author = trim((string) ($row['author'] ?? ''));
            $isbn = strtoupper((string) preg_replace('/[^0-9Xx]/', '', (string) ($row['isbn'] ?? '')));
            $isbn = in_array(strlen($isbn), [10, 13], true) ? $isbn : '';

            if ($title === '') {
                $skipped++;

                continue;
            }

            $exists = BookWish::query()->where(static function ($query) use ($isbn, $title, $author): void {
                if ($isbn !== '') {
                    $query->where('isbn', $isbn);

                    return;
                }

                $query->whereRaw('lower(title) = ?', [mb_strtolower($title)])->where('author', $author !== '' ? $author : null);
            })->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            $wish = new BookWish([
                'patron_id' => null,
                'title' => mb_substr($title, 0, 255),
                'author' => $author !== '' ? mb_substr($author, 0, 255) : null,
                'isbn' => $isbn !== '' ? $isbn : null,
                'note' => trim((string) ($row['note'] ?? '')) !== '' ? mb_substr(trim((string) $row['note']), 0, 500) : null,
                'contact_name' => trim((string) ($row['name'] ?? '')) !== '' ? mb_substr(trim((string) $row['name']), 0, 120) : null,
                'contact_email' => filter_var(trim((string) ($row['email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: null,
                'status' => WishStatus::New,
            ]);

            $stamp = strtotime((string) ($row['created_at'] ?? ''));

            if ($stamp !== false) {
                $wish->created_at = Carbon::createFromTimestamp($stamp);
                $wish->updated_at = $wish->created_at;
            }

            $wish->save();
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /** @return list<array<string, mixed>> */
    private function rows(string $path): array
    {
        try {
            $decoded = json_decode((string) @file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new CirculationRuleViolation(['Die Datei ist keine gültige JSON-Datei.']);
        }

        if (is_array($decoded)) {
            foreach ($decoded as $entry) {
                if (is_array($entry) && ($entry['type'] ?? null) === 'table' && is_array($entry['data'] ?? null)) {
                    return array_values(array_filter($entry['data'], 'is_array'));
                }
            }

            if (array_is_list($decoded) && ($decoded === [] || is_array($decoded[0]))) {
                return array_values(array_filter($decoded, 'is_array'));
            }
        }

        throw new CirculationRuleViolation(['Die Datei enthält keine Tabelle bookWishes (phpMyAdmin-Export als JSON).']);
    }
}
