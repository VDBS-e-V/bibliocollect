<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Series;
use App\Modules\Catalog\Support\SeriesParser;
use Illuminate\Support\Str;

/** Ordnet Ausgaben anhand ihrer Reihenangabe (`series_statement`) einer Reihe mit Bandnummer zu. */
final class SeriesService
{
    /** Bekannte Verlags- und Taschenbuchreihen des Altbestands: keine Lesereihen, deshalb von Anfang an ausgeblendet. */
    private const IMPRINTS = [
        'dtv', 'btb', 'rororo', 'rowohlt', 'fischer', 'heyne', 'goldmann', 'ullstein', 'piper', 'carlsen', 'oetinger', 'arena', 'omnibus',
        'blanvalet', 'knaur', 'insel', 'suhrkamp', 'reclam', 'cbj', 'cbt', 'bvt', 'list', 'kiwi', 'diogenes', 'hanser', 'beltz', 'loewe',
        'thienemann', 'gulliver', 'ueberreuter', 'dressler', 'sauerlander', 'bastei lubbe', 'rororo rotfuchs', 'fischer schatzinsel',
    ];

    /** Namensendungen, an denen man Verlagsreihen erkennt („Ravensburger Taschenbuch“, „Gullivers Bücher“). */
    private const IMPRINT_ENDINGS = [' taschenbuch', ' taschenbucher', ' bucher', ' bibliothek', ' universal bibliothek'];

    /** Setzt `series_id` und `series_volume` am Modell (speichert nicht). */
    public function assign(Edition $edition): void
    {
        $parsed = SeriesParser::parse($edition->series_statement);

        if ($parsed === null) {
            $edition->series_id = null;
            $edition->series_volume = null;

            return;
        }

        $series = $this->resolve($parsed['name'], $parsed['key'], $edition->publisher_name);
        $edition->series_id = (string) $series->getKey();
        $edition->series_volume = $parsed['volume'];
    }

    /**
     * Ausgaben mit Reihenangabe (neu) zuordnen, zum Beispiel nach dem Altbestand-Import oder Massenänderungen.
     *
     * @return int Zahl der geänderten Ausgaben
     */
    public function syncAll(): int
    {
        $changed = 0;

        Edition::query()->whereNotNull('series_statement')->where('series_statement', '!=', '')->orderBy('id')->chunk(200, function ($editions) use (&$changed): void {
            foreach ($editions as $edition) {
                $before = [$edition->series_id, $edition->series_volume];
                $this->assign($edition);

                if ($before !== [$edition->series_id, $edition->series_volume]) {
                    $edition->saveQuietly();
                    $changed++;
                }
            }
        });

        return $changed;
    }

    private function resolve(string $name, string $key, ?string $publisher): Series
    {
        $series = Series::query()->where('key', $key)->first();

        if ($series instanceof Series) {
            // Eine saubere Schreibweise ersetzt eine mit zerstörtem Umlaut („Bu?cher“).
            if (str_contains($series->name, '?') && ! str_contains($name, '?')) {
                $series->update(['name' => $name]);
            }

            return $series;
        }

        return Series::query()->create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'key' => $key,
            // Gleicht die „Reihe“ dem Verlag, ist es meist eine Verlags- oder Taschenbuchreihe und kein Lesezusammenhang.
            'is_hidden' => $this->looksLikePublisherLine($key, $publisher),
        ]);
    }

    private function looksLikePublisherLine(string $key, ?string $publisher): bool
    {
        if (in_array($key, self::IMPRINTS, true)) {
            return true;
        }

        foreach (self::IMPRINT_ENDINGS as $ending) {
            if (str_ends_with($key, $ending)) {
                return true;
            }
        }

        $publisherKey = SeriesParser::key((string) $publisher);

        if ($publisherKey === '') {
            return false;
        }

        return $key === $publisherKey || str_starts_with($key, $publisherKey.' ') || str_starts_with($publisherKey, $key.' ');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::limit(Str::slug(str_replace('?', '', $name)) ?: 'reihe', 120, '');
        $slug = $base;
        $counter = 2;

        while (Series::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
