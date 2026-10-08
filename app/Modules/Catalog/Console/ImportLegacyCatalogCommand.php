<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Console;

use App\Modules\Catalog\Actions\ImportCatalogShelvesFromSignaturesAction;
use App\Modules\Catalog\Actions\ImportLegacyCatalogAction;
use App\Modules\Catalog\Exceptions\LegacyCatalogImportException;
use Illuminate\Console\Command;

final class ImportLegacyCatalogCommand extends Command
{
    protected $signature = 'catalog:legacy:import
        {media : Pfad zum JSON-Export von mediaList}
        {--topics= : Optionaler JSON-Export von mediaTopicList}
        {--signatures= : Optionaler JSON-Export von mediaSignatures}';

    protected $description = 'Importiert den alten BiblioCollect-Katalog transaktional in das aktuelle Catalog-Modell.';

    public function handle(ImportLegacyCatalogAction $import, ImportCatalogShelvesFromSignaturesAction $shelves): int
    {
        try {
            $report = $import->execute(
                $this->absolutePath((string) $this->argument('media')),
                $this->optionalPath($this->option('topics')),
                $this->optionalPath($this->option('signatures')),
            );
        } catch (LegacyCatalogImportException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $shelves->execute();

        $this->table(
            ['Kennzahl', 'Wert'],
            collect($report->summary)
                ->map(static fn (int $value, string $key): array => [$key, $value])
                ->values()
                ->all(),
        );

        if ($report->warnings !== []) {
            $this->warn('Import mit Warnungen abgeschlossen:');
            foreach ($report->warnings as $warning) {
                $this->line(' - '.$warning);
            }
        }

        $this->info('Legacy-Katalogimport erfolgreich abgeschlossen.');

        return self::SUCCESS;
    }

    private function optionalPath(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $this->absolutePath($value) : null;
    }

    private function absolutePath(string $path): string
    {
        if ($path === '') {
            return $path;
        }

        if (preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }
}
