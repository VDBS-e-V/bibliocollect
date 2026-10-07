<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Patrons\Models\PatronCardDesign;
use Illuminate\Support\Facades\Storage;

final readonly class DeletePatronCardDesignAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(PatronCardDesign $design): void
    {
        $this->audit->record('patron_cards.design.deleted', 'Ausweismotiv gelöscht.', $design, ['side' => $design->side]);

        $design->delete();

        // Eigene Uploads gehen mit; mitgelieferte Motive bleiben als Datei erhalten.
        if ($design->isUpload() && ! PatronCardDesign::query()->where('path', $design->path)->exists()) {
            Storage::disk('card_designs')->delete(basename($design->path));
        }
    }
}
