<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Console;

use App\Modules\Catalog\Contracts\CatalogCoverProvider;
use App\Modules\Catalog\Jobs\RefreshEditionCoverJob;
use App\Modules\Catalog\Models\Edition;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

final class QueueCatalogCoverRefreshCommand extends Command
{
    protected $signature = 'catalog:covers:queue
        {--limit=100 : Maximale Zahl der Cover-Jobs pro Lauf}
        {--force : Auch Ausgaben mit bereits lokal vorhandenem Cover erneut einreihen}';

    protected $description = 'Stellt Cover-Aktualisierungen in die Queue; die öffentliche Suche verwendet ausschließlich lokal gespeicherte Cover.';

    public function handle(CatalogCoverProvider $provider): int
    {
        if (! $provider->configured()) {
            $this->warn('Es ist noch kein externer Cover-Provider konfiguriert. Es wurden keine Jobs eingereiht.');

            return self::FAILURE;
        }

        $limit = max(1, min((int) $this->option('limit'), 1000));
        $force = (bool) $this->option('force');
        $query = Edition::query()
            ->where(function (Builder $identifierQuery): void {
                $identifierQuery
                    ->whereNotNull('isbn')
                    ->orWhereNotNull('source_record_id');
            })
            ->orderBy('id');

        if (! $force) {
            $query->whereNull('cover_path');
        }

        $editionIds = $query
            ->limit($limit)
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();

        foreach ($editionIds as $editionId) {
            RefreshEditionCoverJob::dispatch($editionId);
        }

        $this->info(count($editionIds).' Cover-Aktualisierung(en) wurden in die Queue gestellt.');

        return self::SUCCESS;
    }
}
