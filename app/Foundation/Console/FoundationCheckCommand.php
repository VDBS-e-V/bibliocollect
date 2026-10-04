<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use App\Foundation\Support\ModuleRegistry;
use App\Foundation\Support\SurfaceRegistry;
use App\Foundation\Validation\FoundationValidator;
use Illuminate\Console\Command;

final class FoundationCheckCommand extends Command
{
    protected $signature = 'foundation:check';

    protected $description = 'Validate module manifests, dependencies, surfaces and foundation structure.';

    public function handle(
        FoundationValidator $validator,
        ModuleRegistry $modules,
        SurfaceRegistry $surfaces,
    ): int {
        $errors = $validator->validate();

        if ($errors !== []) {
            $this->error('Foundation validation failed.');

            foreach ($errors as $error) {
                $this->line(' - '.$error);
            }

            return self::FAILURE;
        }

        $this->info('Foundation validation passed.');
        $this->line(sprintf('Modules: %d | Surfaces: %d', count($modules->all()), count($surfaces->all())));

        return self::SUCCESS;
    }
}
