<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Providers;

use Illuminate\Support\ServiceProvider;

final class CirculationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
