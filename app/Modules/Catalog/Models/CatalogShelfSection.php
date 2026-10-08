<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\ShelfSectionKind;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ein Eintrag der Standortstruktur: Bereichsgruppe, Bereich oder Regal. Ein Regalbrett gehört zu einem Regal, das Regal zu einem
 * Bereich, der Bereich zu einer Bereichsgruppe.
 *
 * @property string $id
 * @property string|null $parent_id
 * @property ShelfSectionKind $kind
 * @property string $code
 * @property string|null $name
 * @property string|null $description
 * @property int $sort_order
 */
final class CatalogShelfSection extends Model
{
    use HasUlids;

    protected $table = 'catalog_shelf_sections';

    /** @var list<string> */
    protected $fillable = ['parent_id', 'kind', 'code', 'name', 'description', 'sort_order'];

    /** @return BelongsTo<CatalogShelfSection, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<CatalogShelfSection, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('code');
    }

    /**
     * Regalbretter dieses Regals.
     *
     * @return HasMany<CatalogShelf, $this>
     */
    public function shelves(): HasMany
    {
        return $this->hasMany(CatalogShelf::class, 'section_id')->orderBy('sort_order')->orderBy('code');
    }

    /** Anzeige: „Bereich A“ oder „Bereich A · Wand links“. */
    public function display(): string
    {
        $base = $this->kind->label().' '.$this->code;

        return $this->name !== null && $this->name !== '' ? $base.' · '.$this->name : $base;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['kind' => ShelfSectionKind::class, 'sort_order' => 'integer'];
    }
}
