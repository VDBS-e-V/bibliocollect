<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Demo-Daten werden in der Produktionsumgebung nicht angelegt.');

            return;
        }

        $this->call(DemoSeeder::class);
    }
}
