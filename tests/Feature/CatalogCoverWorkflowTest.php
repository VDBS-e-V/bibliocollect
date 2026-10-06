<?php

declare(strict_types=1);

use App\Modules\Catalog\Actions\RefreshEditionCoverAction;
use App\Modules\Catalog\Contracts\CatalogCoverProvider;
use App\Modules\Catalog\DTOs\CatalogCoverImage;
use App\Modules\Catalog\Jobs\RefreshEditionCoverJob;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\CatalogCoverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('stores provider covers locally and exposes only the local public URL', function (): void {
    Storage::fake('public');
    config()->set('catalog.covers.disk', 'public');

    $title = Title::query()->create(['preferred_title' => 'Cover-Test']);
    $edition = Edition::query()->create([
        'title_id' => $title->getKey(),
        'isbn' => '9783000000117',
    ]);

    $provider = new class implements CatalogCoverProvider
    {
        public function configured(): bool
        {
            return true;
        }

        public function fetch(Edition $edition): ?CatalogCoverImage
        {
            return new CatalogCoverImage(
                contents: base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=', true) ?: '',
                mimeType: 'image/png',
                source: 'test-provider',
                sourceReference: 'https://covers.example.test/remote.png',
            );
        }
    };

    app()->instance(CatalogCoverProvider::class, $provider);

    expect(app(RefreshEditionCoverAction::class)->execute($edition))->toBeTrue();

    $edition->refresh();
    $path = $edition->getAttribute('cover_path');

    expect($path)->toBeString()
        ->and($edition->getAttribute('cover_status'))->toBe('ready')
        ->and($edition->getAttribute('cover_source'))->toBe('test-provider')
        ->and($edition->getAttribute('cover_source_reference'))->toBe('https://covers.example.test/remote.png');

    Storage::disk('public')->assertExists((string) $path);

    $url = app(CatalogCoverService::class)->localUrlForTitle($title->fresh('editions'));

    expect($url)->not->toBeNull()
        ->and($url)->toContain('catalog/covers/')
        ->and($url)->not->toContain('covers.example.test');
});

it('queues cover refresh work instead of downloading during catalog requests', function (): void {
    Queue::fake();

    $title = Title::query()->create(['preferred_title' => 'Queue-Cover']);
    $edition = Edition::query()->create([
        'title_id' => $title->getKey(),
        'isbn' => '9783000000124',
    ]);

    app()->instance(CatalogCoverProvider::class, new class implements CatalogCoverProvider
    {
        public function configured(): bool
        {
            return true;
        }

        public function fetch(Edition $edition): ?CatalogCoverImage
        {
            throw new RuntimeException('The queue command must not fetch covers synchronously.');
        }
    });

    $this->artisan('catalog:covers:queue', ['--limit' => 10])
        ->expectsOutput('1 Cover-Aktualisierung(en) wurden in die Queue gestellt.')
        ->assertSuccessful();

    Queue::assertPushed(
        RefreshEditionCoverJob::class,
        static fn (RefreshEditionCoverJob $job): bool => $job->editionId === (string) $edition->getKey(),
    );
});

it('keeps the cover cache migration rollback capable', function (): void {
    expect(Schema::hasColumn('catalog_editions', 'cover_path'))->toBeTrue()
        ->and(Schema::hasColumn('catalog_editions', 'cover_status'))->toBeTrue();

    $this->artisan('migrate:rollback', ['--step' => 1, '--force' => true])->assertSuccessful();

    expect(Schema::hasColumn('catalog_editions', 'cover_path'))->toBeFalse()
        ->and(Schema::hasColumn('catalog_editions', 'cover_status'))->toBeFalse();
});

it('never exposes the cover source reference on public catalog pages', function (): void {
    $title = Title::query()->create(['preferred_title' => 'Cover-Datenschutz', 'sort_title' => 'Cover-Datenschutz']);
    Edition::query()->create([
        'title_id' => $title->getKey(),
        'cover_status' => 'pending',
        'cover_source' => 'test-provider',
        'cover_source_reference' => 'https://covers.example.test/geheim-123.png',
    ]);

    $this->get(route('public.catalog.index'))
        ->assertOk()
        ->assertSee('Cover-Datenschutz')
        ->assertDontSee('covers.example.test')
        ->assertDontSee('geheim-123');

    $this->get(route('public.catalog.show', $title->getKey()))
        ->assertOk()
        ->assertDontSee('covers.example.test')
        ->assertDontSee('geheim-123');
});
