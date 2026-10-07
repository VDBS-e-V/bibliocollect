<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Copy;
use Illuminate\Support\Facades\DB;

/**
 * Plant Etiketten auf Vorrat: Welche siebenstelligen Inventarnummern dürfen gedruckt werden? Nummern, die schon einem
 * Exemplar gehören, werden nie gedruckt. Nummern, deren Etiketten schon einmal auf Vorrat gedruckt wurden, werden
 * übersprungen, außer man verlangt den Neudruck ausdrücklich (zum Beispiel für ein verschmutztes Blatt).
 */
final class InventoryLabelPlanner
{
    /** Höchstzahl je Druck: 20 Bögen. */
    public const MAX_LABELS = 480;

    private const MAX_NUMBER = 9_999_999;

    /**
     * Fortlaufend ab einer Nummer die nächsten freien Nummern.
     *
     * @return array{numbers: list<string>, used: list<string>, printed: list<string>, last: ?int}
     */
    public function sequence(int $start, int $count, bool $reprint = false): array
    {
        $count = max(1, min($count, self::MAX_LABELS));
        $start = max(1, min($start, self::MAX_NUMBER));
        $used = $this->used();
        $printed = $reprint ? [] : $this->printed();
        $numbers = [];
        $skippedUsed = [];
        $skippedPrinted = [];
        $last = null;

        for ($number = $start; $number <= self::MAX_NUMBER && count($numbers) < $count; $number++) {
            $text = $this->format($number);
            $last = $number;

            if (isset($used[$text])) {
                $skippedUsed[] = $text;
            } elseif (isset($printed[$text])) {
                $skippedPrinted[] = $text;
            } else {
                $numbers[] = $text;
            }
        }

        return ['numbers' => $numbers, 'used' => $skippedUsed, 'printed' => $skippedPrinted, 'last' => $last];
    }

    /**
     * Die Lücken der laufenden Reihe: Nummern zwischen \$from und \$to, die noch keinem Exemplar gehören.
     *
     * @return array{numbers: list<string>, total: int, truncated: bool, printed: list<string>}
     */
    public function gaps(int $from, int $to, bool $reprint = false): array
    {
        $from = max(1, $from);
        $to = min(self::MAX_NUMBER, max($from, $to));
        $used = $this->used();
        $printed = $reprint ? [] : $this->printed();
        $all = [];
        $skippedPrinted = [];

        for ($number = $from; $number <= $to; $number++) {
            $text = $this->format($number);

            if (isset($used[$text])) {
                continue;
            }

            if (isset($printed[$text])) {
                $skippedPrinted[] = $text;

                continue;
            }

            $all[] = $text;
        }

        return ['numbers' => array_slice($all, 0, self::MAX_LABELS), 'total' => count($all), 'truncated' => count($all) > self::MAX_LABELS, 'printed' => $skippedPrinted];
    }

    /**
     * Niedrigste, höchste vergebene und zuletzt gedruckte Nummer für die Vorschläge im Formular.
     *
     * @return array{lowest: ?int, highest: ?int, lastPrinted: ?int, usedCount: int}
     */
    public function overview(): array
    {
        $used = array_keys($this->used());
        sort($used);
        $printed = array_keys($this->printed());
        sort($printed);

        return [
            'lowest' => $used === [] ? null : (int) $used[0],
            'highest' => $used === [] ? null : (int) $used[count($used) - 1],
            'lastPrinted' => $printed === [] ? null : (int) $printed[count($printed) - 1],
            'usedCount' => count($used),
        ];
    }

    /**
     * Merkt sich, dass diese Nummern gedruckt wurden.
     *
     * @param  list<string>  $numbers
     */
    public function markPrinted(array $numbers, ?int $userId): void
    {
        $now = now();

        foreach (array_chunk($numbers, 200) as $chunk) {
            DB::table('catalog_printed_labels')->upsert(
                array_map(static fn (string $number): array => ['number' => $number, 'printed_at' => $now, 'printed_by_user_id' => $userId], $chunk),
                ['number'],
                ['printed_at', 'printed_by_user_id'],
            );
        }
    }

    public function format(int $number): string
    {
        return str_pad((string) $number, CatalogInventoryNumber::DIGITS, '0', STR_PAD_LEFT);
    }

    /** @return array<string, true> alle siebenstelligen Inventarnummern im Bestand */
    private function used(): array
    {
        $used = [];

        foreach (Copy::query()->pluck('barcode') as $barcode) {
            if (CatalogInventoryNumber::isValid((string) $barcode)) {
                $used[(string) $barcode] = true;
            }
        }

        return $used;
    }

    /** @return array<string, true> */
    private function printed(): array
    {
        $printed = [];

        foreach (DB::table('catalog_printed_labels')->pluck('number') as $number) {
            $printed[(string) $number] = true;
        }

        return $printed;
    }
}
