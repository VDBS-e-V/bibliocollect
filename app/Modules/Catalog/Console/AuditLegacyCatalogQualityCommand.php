<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Console;

use App\Modules\Catalog\Exceptions\LegacyCatalogImportException;
use App\Modules\Catalog\Legacy\LegacyCatalogQualityAuditor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class AuditLegacyCatalogQualityCommand extends Command
{
    protected $signature = 'catalog:legacy:audit-quality
        {media : Pfad zum JSON-Export von mediaList}
        {--output= : Optionaler Zielpfad für einen vollständigen JSON-Bericht}';

    protected $description = 'Prüft Legacy-Metadaten auf Zeichensatz- und Contributor-Qualitätsprobleme ohne Katalog-Writes.';

    public function handle(LegacyCatalogQualityAuditor $auditor): int
    {
        $mediaPath = $this->absolutePath((string) $this->argument('media'));

        try {
            $report = $auditor->audit($mediaPath);
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

        if ($report->issues !== []) {
            $preview = array_slice($report->issues, 0, 15);
            $previewRows = [];

            foreach ($preview as $issue) {
                $previewRows[] = [
                    $issue['barcode'] ?? '-',
                    $issue['dnb_record_id'] ?? '-',
                    $issue['artifact_fields'] === [] ? '-' : implode(', ', $issue['artifact_fields']),
                    implode(', ', $issue['issues']),
                ];
            }

            $this->warn('Beispiele auffälliger Datensätze (max. 15):');
            $this->table(
                ['Barcode', 'DNB', 'Artefaktfelder', 'Hinweise'],
                $previewRows,
            );

            if (count($report->issues) > count($preview)) {
                $this->line(sprintf(
                    'Weitere %d auffällige Datensätze sind im optionalen JSON-Bericht vollständig enthalten.',
                    count($report->issues) - count($preview),
                ));
            }
        }

        $output = $this->option('output');

        if (is_string($output) && trim($output) !== '') {
            $outputPath = $this->absolutePath($output);
            $directory = dirname($outputPath);

            if ($directory !== '.') {
                File::ensureDirectoryExists($directory);
            }

            $payload = [
                'generated_at' => now()->toIso8601String(),
                'source' => $mediaPath,
                'summary' => $report->summary,
                'issues' => $report->issues,
            ];

            File::put(
                $outputPath,
                json_encode(
                    $payload,
                    JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ).PHP_EOL,
            );

            $this->info('JSON-Bericht geschrieben: '.$outputPath);
        }

        $this->info('Qualitätsaudit abgeschlossen. Es wurden keine Katalogdatensätze verändert.');

        return self::SUCCESS;
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
