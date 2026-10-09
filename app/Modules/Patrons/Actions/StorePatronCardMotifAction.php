<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Patrons\Models\PatronCardMotif;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class StorePatronCardMotifAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(string $name, UploadedFile $front, UploadedFile $back): PatronCardMotif
    {
        $motif = PatronCardMotif::query()->create([
            'name' => $name,
            'front_path' => $this->store($front),
            'back_path' => $this->store($back),
            'is_active' => true,
            'sort_order' => ((int) PatronCardMotif::query()->max('sort_order')) + 1,
        ]);

        $this->audit->record('patron_cards.motif.created', 'Ausweismotiv hochgeladen.', $motif);

        return $motif;
    }

    private function store(UploadedFile $file): string
    {
        $extension = $file->guessExtension() === 'png' ? 'png' : 'jpg';
        $fileName = Str::lower((string) Str::ulid()).'.'.$extension;

        Storage::disk('card_designs')->putFileAs('', $file, $fileName);

        return PatronCardMotif::UPLOAD_DIR.'/'.$fileName;
    }
}
