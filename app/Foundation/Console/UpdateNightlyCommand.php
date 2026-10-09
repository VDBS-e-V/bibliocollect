<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use App\Foundation\Support\AlertService;
use App\Foundation\Update\UpdateException;
use App\Foundation\Update\UpdateManager;
use Illuminate\Console\Command;

/**
 * Spielt nachts ein bereitliegendes, neueres Paket ein, wenn das „Automatisch einspielen“ eingeschaltet ist. Der Abschluss
 * (Migrationen, Wartungsmodus beenden) folgt beim nächsten Cron-Aufruf mit dem neuen Code.
 */
final class UpdateNightlyCommand extends Command
{
    protected $signature = 'system:update-nightly';

    protected $description = 'Spielt ein bereitliegendes Update-Paket ein, wenn das automatische Einspielen eingeschaltet ist.';

    public function handle(UpdateManager $updates, AlertService $alerts): int
    {
        if (! $updates->autoEnabled() || $updates->pending() !== null) {
            return self::SUCCESS;
        }

        $package = $updates->newestUsable();

        if ($package === null) {
            return self::SUCCESS;
        }

        try {
            $updates->apply($package);
            $this->info('Update „'.$package.'“ eingespielt, der Abschluss folgt beim nächsten Cron-Aufruf.');
        } catch (UpdateException $exception) {
            $alerts->notify('update-nightly-failed', 'Das automatische Update ist fehlgeschlagen', [$exception->getMessage(), 'Paket: '.$package, 'Zeit: '.now()->toDateTimeString()]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
