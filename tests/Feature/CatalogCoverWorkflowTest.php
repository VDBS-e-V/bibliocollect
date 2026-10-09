<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Actions\RefreshEditionCoverAction;
use App\Modules\Catalog\Contracts\CatalogCoverProvider;
use App\Modules\Catalog\DTOs\CatalogCoverImage;
use App\Modules\Catalog\Jobs\RefreshEditionCoverJob;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\CatalogCoverService;
use App\Modules\Identity\Actions\AssignRoleAction;
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

it('skips editions already checked without a result unless asked to retry them', function (): void {
    Queue::fake();

    $title = Title::query()->create(['preferred_title' => 'Ohne Cover']);
    $missing = Edition::query()->create([
        'title_id' => $title->getKey(),
        'isbn' => '9783000000124',
    ]);
    $missing->forceFill(['cover_status' => 'missing'])->save();

    app()->instance(CatalogCoverProvider::class, new class implements CatalogCoverProvider
    {
        public function configured(): bool
        {
            return true;
        }

        public function fetch(Edition $edition): ?CatalogCoverImage
        {
            return null;
        }
    });

    $this->artisan('catalog:covers:queue')
        ->expectsOutput('0 Cover-Aktualisierung(en) wurden in die Queue gestellt.')
        ->assertSuccessful();

    Queue::assertNothingPushed();

    $this->artisan('catalog:covers:queue', ['--retry-missing' => true])
        ->expectsOutput('1 Cover-Aktualisierung(en) wurden in die Queue gestellt.')
        ->assertSuccessful();

    Queue::assertPushed(
        RefreshEditionCoverJob::class,
        static fn (RefreshEditionCoverJob $job): bool => $job->editionId === (string) $missing->getKey(),
    );
});

it('keeps the cover cache migration rollback capable', function (): void {
    expect(Schema::hasColumn('catalog_editions', 'cover_path'))->toBeTrue()
        ->and(Schema::hasColumn('catalog_editions', 'cover_status'))->toBeTrue();

    // Die Migration wird direkt aufgerufen, damit der Test nicht davon abhängt, welche Migration die jüngste ist.
    $migration = require app_path('Modules/Catalog/database/migrations/2026_10_06_002000_add_catalog_cover_cache.php');

    $migration->down();

    expect(Schema::hasColumn('catalog_editions', 'cover_path'))->toBeFalse()
        ->and(Schema::hasColumn('catalog_editions', 'cover_status'))->toBeFalse();

    $migration->up();

    expect(Schema::hasColumn('catalog_editions', 'cover_path'))->toBeTrue()
        ->and(Schema::hasColumn('catalog_editions', 'cover_status'))->toBeTrue();
});

it('never exposes the cover source reference on public catalog pages', function (): void {
    $title = Title::query()->create(['preferred_title' => 'Cover-Datenschutz', 'sort_title' => 'Cover-Datenschutz']);
    catalogTestWithCopy(Edition::query()->create([
        'title_id' => $title->getKey(),
        'cover_status' => 'pending',
        'cover_source' => 'test-provider',
        'cover_source_reference' => 'https://covers.example.test/geheim-123.png',
    ]));

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

it('builds local cover URLs from the current request host instead of APP_URL', function (): void {
    Storage::fake('public');
    config()->set('catalog.covers.disk', 'public');
    config()->set('app.url', 'http://localhost');

    $title = Title::query()->create(['preferred_title' => 'Host-Test', 'sort_title' => 'Host-Test']);
    $edition = catalogTestWithCopy(Edition::query()->create(['title_id' => $title->getKey()]));
    $edition->forceFill(['cover_path' => 'catalog/covers/host-test.png', 'cover_status' => 'ready'])->save();
    Storage::disk('public')->put('catalog/covers/host-test.png', 'bild');

    // `php artisan serve` oder ein anderer Port: Das Bild muss dort geladen werden, wo auch die Seite herkommt.
    $this->get('http://127.0.0.1:8000/katalog')
        ->assertOk()
        ->assertSee('http://127.0.0.1:8000/storage/catalog/covers/host-test.png', false)
        ->assertDontSee('http://localhost/storage/', false);
});

it('lets the administration queue covers from the system state page, also for titles searched without result', function (): void {
    Queue::fake();
    $admin = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($admin, 'management');

    $title = Title::query()->create(['preferred_title' => 'Cover-Knopf']);
    $open = Edition::query()->create(['title_id' => $title->getKey(), 'isbn' => '9783000000131']);
    $missing = Edition::query()->create(['title_id' => $title->getKey(), 'isbn' => '9783000000148']);
    $missing->forceFill(['cover_status' => 'missing'])->save();

    app()->instance(CatalogCoverProvider::class, new class implements CatalogCoverProvider
    {
        public function configured(): bool
        {
            return true;
        }

        public function fetch(Edition $edition): ?CatalogCoverImage
        {
            return null;
        }
    });

    $this->actingAs($admin)->get(route('administration.system.index'))->assertOk()
        ->assertSee('Cover der Bücher')->assertSee('Einmal ausführen');

    $this->actingAs($admin)->post(route('administration.system.queue-covers'))->assertRedirect(route('administration.system.index'));
    Queue::assertPushed(RefreshEditionCoverJob::class, 1);

    $this->actingAs($admin)->post(route('administration.system.queue-covers'), ['retry_missing' => '1'])->assertRedirect();
    Queue::assertPushed(RefreshEditionCoverJob::class, static fn (RefreshEditionCoverJob $job): bool => $job->editionId === (string) $missing->getKey());
    expect($open->getKey())->not->toBeNull();
});

it('shows the cover progress and the installation state on the system state page', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($admin, 'management');
    $title = Title::query()->create(['preferred_title' => 'Mit Cover']);
    $with = Edition::query()->create(['title_id' => $title->getKey(), 'isbn' => '9783000000155']);
    $with->forceFill(['cover_path' => 'catalog/covers/x.jpg', 'cover_status' => 'ready', 'cover_fetched_at' => now()])->save();
    Edition::query()->create(['title_id' => $title->getKey(), 'isbn' => '9783000000162']);
    $none = Edition::query()->create(['title_id' => $title->getKey(), 'isbn' => '9783000000179']);
    $none->forceFill(['cover_status' => 'missing'])->save();
    Edition::query()->create(['title_id' => $title->getKey()]);

    $page = $this->actingAs($admin)->get(route('administration.system.index'))->assertOk();
    $page->assertSeeText('1 von 4 Ausgaben haben ein Cover')->assertSee('25 %')->assertSee('Suche steht aus')->assertSee('Erfolglos gesucht')->assertSee('Ohne ISBN')
        ->assertSee('Nächtlicher Lauf')->assertSee('Zuletzt geholt')->assertSee('Mit Cover')
        ->assertSee('Installation')->assertSee('Installierte Version')->assertSee('Datenbank-Aktualisierung')->assertSee('auf dem neuesten Stand')->assertSee('Schreibrechte storage');
});
