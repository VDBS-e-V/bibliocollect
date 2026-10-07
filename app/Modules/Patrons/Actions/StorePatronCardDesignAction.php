<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Patrons\Models\PatronCardDesign;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class StorePatronCardDesignAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(string $side, string $name, UploadedFile $file): PatronCardDesign
    {
        $extension = $file->guessExtension() === 'png' ? 'png' : 'jpg';
        $fileName = Str::lower((string) Str::ulid()).'.'.$extension;

        Storage::disk('card_designs')->putFileAs('', $file, $fileName);

        $design = PatronCardDesign::query()->create([
            'side' => $side,
            'name' => $name,
            'path' => PatronCardDesign::UPLOAD_DIR.'/'.$fileName,
            'is_active' => true,
            'sort_order' => ((int) PatronCardDesign::query()->where('side', $side)->max('sort_order')) + 1,
        ]);

        $this->audit->record('patron_cards.design.created', 'Ausweismotiv hochgeladen.', $design, ['side' => $side]);

        return $design;
    }
}
