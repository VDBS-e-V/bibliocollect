<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Events\CopyBecameAvailable;
use App\Modules\Catalog\Models\Copy;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Ein als verloren oder beschädigt eingetragenes Exemplar ist wieder da (gefunden oder repariert) und wird wieder ausleihbar. */
final readonly class MarkCopyFoundAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(Copy $copy): Copy
    {
        $locked = DB::transaction(function () use ($copy): Copy {
            $locked = Copy::query()->whereKey($copy->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [CopyStatus::Lost, CopyStatus::Damaged], true)) {
                throw new InvalidArgumentException('Dieses Exemplar ist nicht als verloren oder beschädigt eingetragen.');
            }

            $previous = $locked->status;
            $locked->forceFill(['status' => CopyStatus::Active])->save();

            $this->audit->record('catalog.copy.found', 'Exemplar wieder verfügbar gemacht (vorher '.$previous->value.').', $locked, ['previous' => $previous->value]);

            return $locked;
        });

        event(new CopyBecameAvailable($locked));

        return $locked;
    }
}
