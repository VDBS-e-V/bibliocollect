<?php

declare(strict_types=1);

namespace App\Modules\Reminders\Providers;

use App\Modules\Reminders\Console\SendRemindersCommand;
use Illuminate\Support\ServiceProvider;

final class RemindersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([SendRemindersCommand::class]);
        }
    }
}
