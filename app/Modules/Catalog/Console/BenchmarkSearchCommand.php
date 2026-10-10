<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Console;

use App\Modules\Catalog\DTOs\CatalogSearchCriteria;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Queries\SearchCatalogTitlesQuery;
use App\Modules\Catalog\Services\SearchSuggestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/** Misst die Katalogsuche auf dem echten System (Datenbank, Bestand), damit man nach Zahlen entscheiden kann, ob sie schnell genug ist. */
final class BenchmarkSearchCommand extends Command
{
    protected $signature = 'catalog:search:benchmark {--runs=5 : Wiederholungen je Suche}';

    protected $description = 'Misst Suchlaufzeiten (Titel, Autor:in, Relevanz, Meintest du, Vorschläge) mit Beispielbegriffen aus dem Bestand.';

    public function handle(SearchCatalogTitlesQuery $search, SearchSuggestionService $suggestions): int
    {
        $runs = max(1, (int) $this->option('runs'));
        $title = Title::query()->inRandomOrder()->value('preferred_title');
        $author = Contributor::query()->inRandomOrder()->value('display_name');

        $titleWord = collect(preg_split('/\s+/u', (string) $title, -1, PREG_SPLIT_NO_EMPTY) ?: [])->sortByDesc(static fn (string $word): int => mb_strlen($word))->first() ?? 'der';
        $authorWord = collect(preg_split('/\s+/u', (string) $author, -1, PREG_SPLIT_NO_EMPTY) ?: [])->sortByDesc(static fn (string $word): int => mb_strlen($word))->first() ?? 'anna';
        $typo = mb_strlen((string) $titleWord) > 4 ? mb_substr((string) $titleWord, 0, 2).mb_substr((string) $titleWord, 3) : (string) $titleWord;

        $this->info(Title::query()->count().' Titel im Bestand. Beispielbegriffe: Titelwort „'.$titleWord.'“, Autor:in „'.$authorWord.'“, Tippfehler „'.$typo.'“.');

        $cases = [
            'Titelwort (nach Titel)' => fn () => $search->paginate(new CatalogSearchCriteria(term: (string) $titleWord, presentCopiesOnly: true)),
            'Titelwort (Relevanz)' => fn () => $search->paginate(new CatalogSearchCriteria(term: (string) $titleWord, sort: 'relevance', presentCopiesOnly: true)),
            'Autor:in (Relevanz)' => fn () => $search->paginate(new CatalogSearchCriteria(term: (string) $authorWord, sort: 'relevance', presentCopiesOnly: true)),
            'Zwei Wörter (Relevanz)' => fn () => $search->paginate(new CatalogSearchCriteria(term: $titleWord.' '.$authorWord, sort: 'relevance', presentCopiesOnly: true)),
            'Meintest du (ohne Zwischenspeicher)' => function () use ($suggestions, $typo): mixed {
                Cache::forget('catalog.search.vocabulary');

                return $suggestions->didYouMean($typo);
            },
            'Meintest du (mit Zwischenspeicher)' => fn () => $suggestions->didYouMean($typo),
            'Vorschläge beim Tippen' => function () use ($suggestions, $titleWord): mixed {
                Cache::forget('catalog.search.complete.'.md5(mb_strtolower(mb_substr((string) $titleWord, 0, 3))).'.8');

                return $suggestions->complete(mb_substr((string) $titleWord, 0, 3));
            },
        ];

        $rows = [];

        foreach ($cases as $label => $run) {
            $times = [];

            for ($i = 0; $i < $runs; $i++) {
                $start = hrtime(true);
                $run();
                $times[] = (hrtime(true) - $start) / 1e6;
            }

            $rows[] = [$label, number_format(array_sum($times) / count($times), 1, ',', '').' ms', number_format(max($times), 1, ',', '').' ms'];
        }

        $this->table(['Suche', 'Mittel', 'Langsamste'], $rows);
        $this->line('Richtwert: Unter 300 ms im Mittel ist für den Alltag unproblematisch. Wird die Suche bei großem Bestand langsamer, kommt ein FULLTEXT-Index in Frage.');

        return self::SUCCESS;
    }
}
