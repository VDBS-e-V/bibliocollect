<?php

declare(strict_types=1);

namespace App\Modules\Content\Services;

use App\Modules\Content\Models\ContentBlock;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

/** Liefert die Textbausteine für die Seiten: nur eingeschaltet, im Zeitfenster und bereinigt. */
final readonly class ContentBlocks
{
    private const TTL = 300;

    public function __construct(private RichTextSanitizer $sanitizer) {}

    /** @return array<string, array{title: string, place: string}> */
    public function definitions(): array
    {
        /** @var array<string, array{title: string, place: string}> $blocks */
        $blocks = (array) config('content.blocks', []);

        return $blocks;
    }

    public function render(string $key): ?string
    {
        if (! array_key_exists($key, $this->definitions())) {
            return null;
        }

        // Nur der Wert „kein Baustein sichtbar“ oder das fertige HTML wird gemerkt; der Tag steckt im Schlüssel, damit Fristen stimmen.
        try {
            $html = Cache::remember($this->cacheKey($key).'.'.now()->toDateString(), self::TTL, function () use ($key): string {
                $block = ContentBlock::query()->where('key', $key)->first();

                if (! $block instanceof ContentBlock || ! $block->isVisibleOn(now())) {
                    return '';
                }

                $html = $this->sanitizer->sanitize($block->body);

                return $this->sanitizer->hasText($html) ? $html : '';
            });
        } catch (QueryException) {
            // Tabelle fehlt noch (Update eingespielt, Migration steht aus): die Seite darf deshalb nie ausfallen.
            return null;
        }

        return $html === '' ? null : $html;
    }

    public function forget(string $key): void
    {
        Cache::forget($this->cacheKey($key).'.'.now()->toDateString());
        Cache::forget($this->cacheKey($key).'.'.now()->subDay()->toDateString());
    }

    private function cacheKey(string $key): string
    {
        return 'content.block.'.$key;
    }
}
