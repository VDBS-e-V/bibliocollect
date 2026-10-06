<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Jobs;

use App\Modules\Catalog\Actions\RefreshEditionCoverAction;
use App\Modules\Catalog\Models\Edition;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class RefreshEditionCoverJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 45;

    public function __construct(public readonly string $editionId) {}

    public function handle(RefreshEditionCoverAction $refresh): void
    {
        $edition = Edition::query()->find($this->editionId);

        if ($edition === null) {
            return;
        }

        $refresh->execute($edition);
    }

    public function failed(Throwable $exception): void
    {
        Edition::query()
            ->whereKey($this->editionId)
            ->update([
                'cover_status' => 'error',
                'cover_checked_at' => now(),
            ]);
    }
}
