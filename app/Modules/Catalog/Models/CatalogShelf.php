<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Regalbrett. Der Code ist der Standort, der am Exemplar steht; die Beschriftung nennt, was dort steht.
 *
 * @property string $id
 * @property string $code
 * @property string|null $label
 * @property int $sort_order
 * @property bool $is_active
 */
final class CatalogShelf extends Model
{
    use HasUlids;

    protected $table = 'catalog_shelves';

    /** @var list<string> */
    protected $fillable = ['code', 'label', 'sort_order', 'is_active'];

    public function display(): string
    {
        return $this->label !== null && $this->label !== '' ? $this->code.' · '.$this->label : $this->code;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }
}
