<?php

declare(strict_types=1);

namespace App\Modules\Privacy\Providers;

use App\Modules\Privacy\Console\AnonymizeExpiredDataCommand;
use Illuminate\Support\ServiceProvider;

final class PrivacyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Auch außerhalb der Konsole registrieren: Der Web-Cron ruft app:cron und die Zeitplan-Befehle über die URL auf.
        $this->commands([AnonymizeExpiredDataCommand::class]);
    }
}
