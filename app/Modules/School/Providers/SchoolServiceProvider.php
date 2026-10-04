<?php

declare(strict_types=1);

namespace App\Modules\School\Providers;

use App\Modules\School\Services\SchoolCalendarService;
use Illuminate\Support\ServiceProvider;

final class SchoolServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SchoolCalendarService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
