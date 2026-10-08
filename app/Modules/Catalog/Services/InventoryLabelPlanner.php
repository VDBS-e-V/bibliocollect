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
     * Merkt sich einen Druckauftrag und seine Nummern als gedruckt.
     *
     * @param  list<string>  $numbers
     * @return int Nummer des Druckauftrags
     */
    public function markPrinted(array $numbers, ?int $userId, string $mode = 'reihe'): int
    {
        $now = now();
        sort($numbers);

        return DB::transaction(function () use ($numbers, $userId, $mode, $now): int {
            $runId = (int) DB::table('catalog_label_runs')->insertGetId([
                'printed_at' => $now,
                'printed_by_user_id' => $userId,
                'mode' => $mode,
                'label_count' => count($numbers),
                'first_number' => $numbers[0],
                'last_number' => $numbers[count($numbers) - 1],
            ]);

            foreach (array_chunk($numbers, 200) as $chunk) {
                DB::table('catalog_printed_labels')->upsert(
                    array_map(static fn (string $number): array => ['number' => $number, 'run_id' => $runId, 'printed_at' => $now, 'printed_by_user_id' => $userId], $chunk),
                    ['number'],
                    ['run_id', 'printed_at', 'printed_by_user_id'],
                );
            }

            return $runId;
        });
    }

    /**
     * Die letzten Druckaufträge, neueste zuerst.
     *
     * @return list<array{id: int, printed_at: string, by: ?string, mode: string, count: int, first: string, last: string, remaining: int}>
     */
    public function runs(int $limit = 10): array
    {
        $rows = DB::table('catalog_label_runs')
            ->leftJoin('users', 'users.id', '=', 'catalog_label_runs.printed_by_user_id')
            ->orderByDesc('catalog_label_runs.id')
            ->limit($limit)
            ->get(['catalog_label_runs.*', 'users.name as user_name']);

        $remaining = DB::table('catalog_printed_labels')->whereIn('run_id', $rows->pluck('id')->all())->selectRaw('run_id, count(*) as total')->groupBy('run_id')->pluck('total', 'run_id');

        return $rows->map(static fn (object $row): array => [
            'id' => (int) $row->id,
            'printed_at' => (string) $row->printed_at,
            'by' => $row->user_name !== null ? (string) $row->user_name : null,
            'mode' => (string) $row->mode,
            'count' => (int) $row->label_count,
            'first' => (string) $row->first_number,
            'last' => (string) $row->last_number,
            'remaining' => (int) ($remaining[$row->id] ?? 0),
        ])->all();
    }

    /** Anzahl aller als gedruckt gespeicherten Nummern. */
    public function printedCount(): int
    {
        return (int) DB::table('catalog_printed_labels')->count();
    }

    /**
     * Nimmt einen Druckauftrag zurück: Seine Nummern gelten nicht mehr als gedruckt und werden wieder vergeben.
     *
     * @return int Anzahl freigegebener Nummern, -1 wenn es den Auftrag nicht gibt
     */
    public function deleteRun(int $runId): int
    {
        return DB::transaction(function () use ($runId): int {
            if (! DB::table('catalog_label_runs')->where('id', $runId)->exists()) {
                return -1;
            }

            $released = DB::table('catalog_printed_labels')->where('run_id', $runId)->delete();
            DB::table('catalog_label_runs')->where('id', $runId)->delete();

            return $released;
        });
    }

    /**
     * Vergisst alle gedruckten Nummern und alle Druckaufträge.
     *
     * @return int Anzahl freigegebener Nummern
     */
    public function clearAll(): int
    {
        return DB::transaction(function (): int {
            $released = DB::table('catalog_printed_labels')->delete();
            DB::table('catalog_label_runs')->delete();

            return $released;
        });
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
