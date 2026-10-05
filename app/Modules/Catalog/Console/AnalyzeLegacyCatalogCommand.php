<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Console;

use App\Modules\Catalog\Exceptions\LegacyCatalogImportException;
use App\Modules\Catalog\Legacy\LegacyCatalogImportAnalyzer;
use Illuminate\Console\Command;

final class AnalyzeLegacyCatalogCommand extends Command
{
    protected $signature = 'catalog:legacy:analyze
        {media : Pfad zum JSON-Export von mediaList}
        {--topics= : Optionaler JSON-Export von mediaTopicList}
        {--signatures= : Optionaler JSON-Export von mediaSignatures}';

    protected $description = 'Analysiert einen alten BiblioCollect/phpMyAdmin-Katalogexport ohne Schreibzugriffe.';

    public function handle(LegacyCatalogImportAnalyzer $analyzer): int
    {
        try {
            $report = $analyzer->analyze(
                $this->absolutePath((string) $this->argument('media')),
                $this->optionalPath($this->option('topics')),
                $this->optionalPath($this->option('signatures')),
            );
        } catch (LegacyCatalogImportException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Kennzahl', 'Wert'],
            collect($report->summary)
                ->map(static fn (int $value, string $key): array => [$key, $value])
                ->values()
                ->all(),
        );

        if ($report->warnings !== []) {
            $this->warn('Warnungen:');
            foreach ($report->warnings as $warning) {
                $this->line(' - '.$warning);
            }
        }

        if ($report->conflicts !== []) {
            $this->error('Blockierende Konflikte:');
            foreach ($report->conflicts as $conflict) {
                $this->line(' - '.$conflict);
            }

            return self::FAILURE;
        }

        $this->info('Analyse abgeschlossen. Es wurden keine Katalogdatensätze verändert.');

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
