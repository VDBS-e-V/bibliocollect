<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use App\Foundation\Support\AlertService;
use App\Foundation\Update\ReleaseSource;
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

    public function handle(UpdateManager $updates, AlertService $alerts, ReleaseSource $source): int
    {
        if (! $updates->autoEnabled() || $updates->pending() !== null) {
            return self::SUCCESS;
        }

        // Ist es eingeschaltet, holt der Server ein neues Release selbst, bevor er nach einem bereitliegenden Paket sucht.
        if ($updates->autoDownloadEnabled()) {
            try {
                $release = $source->latest();

                if ($updates->compare($release['version'], $updates->currentVersion()) === true && ! $updates->hasPackageVersion($release['version'])) {
                    $path = $source->download($release);
                    $updates->store($path, 'bibliocollect-'.$release['tag'].'.zip');
                    $this->info('Release '.$release['tag'].' von GitHub geholt.');
                }
            } catch (UpdateException $exception) {
                $alerts->notify('update-download-failed', 'Das neue Release konnte nicht von GitHub geholt werden', [$exception->getMessage(), 'Zeit: '.now()->toDateTimeString()]);
                $this->warn($exception->getMessage());
            }
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
