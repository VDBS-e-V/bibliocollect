<?php

declare(strict_types=1);

namespace App\Modules\Privacy\Providers;

use App\Modules\Privacy\Console\AnonymizeExpiredDataCommand;
use Illuminate\Support\ServiceProvider;

final class PrivacyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([AnonymizeExpiredDataCommand::class]);
        }
    }
}
