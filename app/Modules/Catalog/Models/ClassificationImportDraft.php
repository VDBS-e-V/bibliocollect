<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Hochgeladene Importdateien für Themen und Regalbretter, die geprüft und noch nicht (oder gerade) übernommen werden.
 *
 * @property string $id
 * @property int $user_id
 * @property string|null $topics_path
 * @property string|null $signatures_path
 * @property string|null $topics_sha256
 * @property string|null $signatures_sha256
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 */
final class ClassificationImportDraft extends Model
{
    use HasUlids;

    public const DISK = 'local';

    public const LIFETIME_MINUTES = 60;

    protected $table = 'catalog_classification_import_drafts';

    /** @var list<string> */
    protected $fillable = ['user_id', 'topics_path', 'signatures_path', 'topics_sha256', 'signatures_sha256', 'expires_at', 'consumed_at'];

    public function isUsable(): bool
    {
        return $this->consumed_at === null && $this->expires_at->isFuture();
    }

    /** Absoluter Pfad einer gespeicherten Datei, oder null (nicht vorhanden). */
    public function absolutePath(?string $relative): ?string
    {
        if ($relative === null || ! Storage::disk(self::DISK)->exists($relative)) {
            return null;
        }

        return Storage::disk(self::DISK)->path($relative);
    }

    /** Stimmen die Dateien auf der Platte noch mit den Prüfsummen vom Hochladen überein? */
    public function filesIntact(): bool
    {
        foreach ([['topics_path', 'topics_sha256'], ['signatures_path', 'signatures_sha256']] as [$pathField, $hashField]) {
            if ($this->{$pathField} === null) {
                continue;
            }

            $path = $this->absolutePath($this->{$pathField});

            if ($path === null || ! hash_equals((string) $this->{$hashField}, (string) hash_file('sha256', $path))) {
                return false;
            }
        }

        return true;
    }

    /** Entfernt die Dateien dieses Entwurfs. */
    public function discardFiles(): void
    {
        foreach ([$this->topics_path, $this->signatures_path] as $path) {
            if ($path !== null) {
                Storage::disk(self::DISK)->delete($path);
            }
        }
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'consumed_at' => 'datetime'];
    }
}
