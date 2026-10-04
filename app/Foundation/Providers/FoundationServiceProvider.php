<?php

declare(strict_types=1);

namespace App\Foundation\Providers;

use App\Foundation\Console\FoundationCheckCommand;
use App\Foundation\Support\ModuleRegistry;
use App\Foundation\Support\SurfaceRegistry;
use Illuminate\Support\ServiceProvider;

final class FoundationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(SurfaceRegistry::class);

        $registry = $this->app->make(ModuleRegistry::class);

        foreach ($registry->enabled() as $module) {
            foreach ($module['providers'] as $provider) {
                if (class_exists($provider)) {
                    $this->app->register($provider);
                }
            }
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FoundationCheckCommand::class,
            ]);
        }
    }
}
