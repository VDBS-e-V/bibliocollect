<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Patrons\Models\PatronCardMotif;
use Illuminate\Support\Facades\Storage;

final readonly class DeletePatronCardMotifAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(PatronCardMotif $motif): void
    {
        $this->audit->record('patron_cards.motif.deleted', 'Ausweismotiv gelöscht.', $motif);

        $motif->delete();

        // Eigene Uploads gehen mit; mitgelieferte Motive bleiben als Datei erhalten.
        Storage::disk('card_designs')->delete($motif->uploadFiles());
    }
}
