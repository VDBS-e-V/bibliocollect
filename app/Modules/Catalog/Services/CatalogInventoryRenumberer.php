<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Models\Copy;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Stellt alte Inventarnummern (nicht genau sieben Ziffern) auf neue um. Das passiert nie von selbst, sondern nur auf
 * ausdrücklichen Auftrag über die Oberfläche. Neue Nummern laufen fortlaufend ab der höchsten vorhandenen
 * siebenstelligen Nummer.
 */
final readonly class CatalogInventoryRenumberer
{
    public function __construct(private AuditRecorder $audit) {}

    /**
     * Exemplare mit alter Nummer, nach Nummer sortiert.
     *
     * @return list<Copy>
     */
    public function legacyCopies(?string $term = null): array
    {
        $copies = Copy::query()
            ->with('edition.title')
            ->when($term !== null && $term !== '', static function ($query) use ($term): void {
                $like = '%'.$term.'%';
                $query->where(static function ($inner) use ($like): void {
                    $inner->where('barcode', 'like', $like)->orWhereHas('edition.title', static fn ($title) => $title->where('preferred_title', 'like', $like));
                });
            })
            ->orderBy('barcode')
            ->get()
            ->filter(static fn (Copy $copy): bool => ! CatalogInventoryNumber::isValid($copy->barcode))
            ->values();

        return $copies->all();
    }

    public function legacyCount(): int
    {
        return Copy::query()->pluck('barcode')->filter(static fn (mixed $barcode): bool => ! CatalogInventoryNumber::isValid((string) $barcode))->count();
    }

    /** Die nächste freie siebenstellige Nummer. */
    public function nextNumber(): int
    {
        $max = 0;

        foreach (Copy::query()->pluck('barcode') as $barcode) {
            if (CatalogInventoryNumber::isValid((string) $barcode)) {
                $max = max($max, (int) $barcode);
            }
        }

        return $max + 1;
    }

    /**
     * Vergibt den ausgewählten Exemplaren neue Nummern in der Reihenfolge ihrer alten Nummern.
     *
     * @param  list<string>  $copyIds
     * @return list<array{copy: Copy, old: string, new: string}>
     */
    public function renumber(array $copyIds): array
    {
        return DB::transaction(function () use ($copyIds): array {
            $copies = Copy::query()
                ->whereIn('id', $copyIds)
                ->lockForUpdate()
                ->orderBy('barcode')
                ->get()
                ->filter(static fn (Copy $copy): bool => ! CatalogInventoryNumber::isValid($copy->barcode))
                ->values();

            $next = $this->nextNumber();

            if ($next + $copies->count() - 1 > 9_999_999) {
                throw new RuntimeException('Die siebenstelligen Inventarnummern reichen für diese Auswahl nicht mehr aus.');
            }

            $result = [];

            foreach ($copies as $copy) {
                $old = $copy->barcode;
                $new = str_pad((string) $next++, CatalogInventoryNumber::DIGITS, '0', STR_PAD_LEFT);

                $copy->forceFill(['barcode' => $new])->save();
                $this->audit->record('catalog.copy.renumbered', 'Inventarnummer umgestellt.', $copy, ['old' => $old, 'new' => $new]);

                $result[] = ['copy' => $copy, 'old' => $old, 'new' => $new];
            }

            return $result;
        });
    }
}
