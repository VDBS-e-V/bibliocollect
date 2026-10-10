<?php

declare(strict_types=1);

use App\Foundation\Update\ReleaseSource;
use App\Foundation\Update\UpdateManager;
use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function releaseUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

/** Setzt die nachgestellten Antworten zurück (sonst gewinnt die zuerst eingerichtete). */
function releaseHttp(): void
{
    Http::swap(new HttpFactory);
}

/** Ein gültiges Paket als Bytes. */
function releaseZipBytes(string $version): string
{
    $path = tempnam(sys_get_temp_dir(), 'relzip');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach (['artisan', 'composer.json', 'bootstrap/app.php', 'vendor/autoload.php', 'public/index.php'] as $file) {
        $zip->addFromString($file, '<?php');
    }

    $zip->addFromString('VERSION', $version);
    $zip->close();
    $bytes = (string) file_get_contents($path);
    unlink($path);

    return $bytes;
}

/**
 * Stellt GitHub nach: Release-Abfrage, Paket und Prüfsumme.
 *
 * @param  array<string, mixed>  $override  Felder der Release-Antwort, die abweichen sollen
 */
function releaseFake(string $tag = 'v9.9.9', array $override = [], ?string $bytes = null, ?string $checksum = null): string
{
    $bytes ??= releaseZipBytes($tag);
    $base = 'https://github.com/VDBS-e-V/bibliocollect/releases/download/'.$tag.'/';
    $json = array_merge([
        'tag_name' => $tag, 'name' => 'Version '.$tag, 'body' => "Neu: Merkliste\nBehoben: Etiketten", 'draft' => false, 'prerelease' => false,
        'published_at' => '2026-10-10T10:00:00Z',
        'assets' => [
            ['name' => 'bibliocollect-'.$tag.'.zip', 'size' => strlen($bytes), 'browser_download_url' => $base.'bibliocollect-'.$tag.'.zip'],
            ['name' => 'bibliocollect-'.$tag.'.zip.sha256', 'size' => 90, 'browser_download_url' => $base.'bibliocollect-'.$tag.'.zip.sha256'],
        ],
    ], $override);

    releaseHttp();
    Http::fake([
        'api.github.com/*' => Http::response($json),
        $base.'bibliocollect-'.$tag.'.zip' => Http::response($bytes),
        $base.'bibliocollect-'.$tag.'.zip.sha256' => Http::response(($checksum ?? hash('sha256', $bytes)).'  bibliocollect-'.$tag.'.zip'."\n"),
    ]);

    return $bytes;
}

beforeEach(function (): void {
    $this->root = sys_get_temp_dir().'/bc-release-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($this->root.'/updates');
    File::ensureDirectoryExists($this->root.'/target');
    File::put($this->root.'/target/VERSION', 'v0.68.1');
    File::put($this->root.'/target/.env', 'APP_KEY=geheim');
    config(['foundation.update.directory' => $this->root.'/updates', 'foundation.update.target' => $this->root.'/target', 'foundation.update.backup' => false]);
    Cache::flush();
});

afterEach(function (): void {
    Artisan::call('up');
    UpdateManager::$applied = false;
    File::deleteDirectory($this->root);
});

it('looks up the newest stable release and shows version, size and changes without downloading anything', function (): void {
    releaseFake('v9.9.9');
    $admin = releaseUser('management');

    $this->actingAs($admin)->post(route('administration.update.release.check'))->assertRedirect(route('administration.update.index'));
    $this->actingAs($admin)->get(route('administration.update.index'))->assertOk()
        ->assertSee('Version v9.9.9')->assertSee('neuer als installiert')->assertSee('Neu: Merkliste')->assertSee('Paket holen');

    expect(File::glob($this->root.'/updates/*.zip'))->toBe([])
        ->and(AuditEvent::query()->where('action', 'system.update.release_checked')->count())->toBe(1);
});

it('fetches the package, checks the checksum and makes it ready to apply', function (): void {
    releaseFake('v9.9.9');
    $admin = releaseUser('management');

    $this->actingAs($admin)->post(route('administration.update.release.check'));
    $this->actingAs($admin)->post(route('administration.update.release.fetch'), ['tag' => 'v9.9.9'])->assertRedirect(route('administration.update.index'))->assertSessionHas('update_success');

    expect(is_file($this->root.'/updates/bibliocollect-v9.9.9.zip'))->toBeTrue()
        ->and(app(UpdateManager::class)->newestUsable())->toBe('bibliocollect-v9.9.9.zip')
        ->and(AuditEvent::query()->where('action', 'system.update.downloaded')->count())->toBe(1);

    $this->actingAs($admin)->get(route('administration.update.index'))->assertSee('liegt schon bereit')->assertSee('neuer als installiert');
});

it('discards a package whose checksum does not match', function (): void {
    releaseFake('v9.9.9', [], null, str_repeat('a', 64));
    $admin = releaseUser('management');

    $this->actingAs($admin)->post(route('administration.update.release.check'));
    $this->actingAs($admin)->post(route('administration.update.release.fetch'), ['tag' => 'v9.9.9'])->assertSessionHasErrors('release');

    expect(File::glob($this->root.'/updates/*.zip'))->toBe([]);
});

it('discards a package that does not have the announced size', function (): void {
    $bytes = releaseZipBytes('v9.9.9');
    $base = 'https://github.com/VDBS-e-V/bibliocollect/releases/download/v9.9.9/';
    releaseFake('v9.9.9', ['assets' => [
        ['name' => 'bibliocollect-v9.9.9.zip', 'size' => strlen($bytes) + 5, 'browser_download_url' => $base.'bibliocollect-v9.9.9.zip'],
        ['name' => 'bibliocollect-v9.9.9.zip.sha256', 'size' => 90, 'browser_download_url' => $base.'bibliocollect-v9.9.9.zip.sha256'],
    ]], $bytes);
    $admin = releaseUser('management');

    $this->actingAs($admin)->post(route('administration.update.release.check'));
    $this->actingAs($admin)->post(route('administration.update.release.fetch'), ['tag' => 'v9.9.9'])->assertSessionHasErrors('release');

    expect(File::glob($this->root.'/updates/*.zip'))->toBe([]);
});

it('does not keep a file that is no BiblioCollect package even if the checksum matches', function (): void {
    releaseFake('v9.9.9', [], 'kein zip');
    $admin = releaseUser('management');

    $this->actingAs($admin)->post(route('administration.update.release.check'));
    $this->actingAs($admin)->post(route('administration.update.release.fetch'), ['tag' => 'v9.9.9'])->assertSessionHasErrors('release');

    expect(File::glob($this->root.'/updates/*.zip'))->toBe([]);
});

it('only trusts stable releases of the own repository with a checksum', function (): void {
    $source = app(ReleaseSource::class);
    $own = 'https://github.com/VDBS-e-V/bibliocollect/releases/download/v9.9.9/';

    releaseFake('v9.9.9', ['prerelease' => true]);
    expect(fn () => $source->latest())->toThrow(Exception::class, 'kein stabiles Release');

    releaseFake('v9.9.9', ['tag_name' => 'latest']);
    expect(fn () => $source->latest())->toThrow(Exception::class, 'kein stabiles Release');

    // Paket und Prüfsumme von einer fremden Adresse zählen nicht.
    releaseFake('v9.9.9', ['assets' => [
        ['name' => 'bibliocollect-v9.9.9.zip', 'size' => 100, 'browser_download_url' => 'https://evil.example/bibliocollect-v9.9.9.zip'],
        ['name' => 'bibliocollect-v9.9.9.zip.sha256', 'size' => 90, 'browser_download_url' => 'https://evil.example/bibliocollect-v9.9.9.zip.sha256'],
    ]]);
    expect(fn () => $source->latest())->toThrow(Exception::class, 'gehört kein Paket');

    // Ohne Prüfsumme wird nichts geholt.
    releaseFake('v9.9.9', ['assets' => [['name' => 'bibliocollect-v9.9.9.zip', 'size' => 100, 'browser_download_url' => $own.'bibliocollect-v9.9.9.zip']]]);
    expect(fn () => $source->latest())->toThrow(Exception::class, 'fehlt die Prüfsumme');

    // Unglaubwürdige Größe.
    releaseFake('v9.9.9', ['assets' => [
        ['name' => 'bibliocollect-v9.9.9.zip', 'size' => 500 * 1024 * 1024, 'browser_download_url' => $own.'bibliocollect-v9.9.9.zip'],
        ['name' => 'bibliocollect-v9.9.9.zip.sha256', 'size' => 90, 'browser_download_url' => $own.'bibliocollect-v9.9.9.zip.sha256'],
    ]]);
    expect(fn () => $source->latest())->toThrow(Exception::class, 'unglaubwürdige Größe');
});

it('explains clearly when there is no release yet', function (): void {
    releaseHttp();
    Http::fake(['api.github.com/*' => Http::response([], 404)]);
    $admin = releaseUser('management');

    $this->actingAs($admin)->post(route('administration.update.release.check'))->assertRedirect(route('administration.update.index'));
    $this->actingAs($admin)->get(route('administration.update.index'))->assertSee('noch kein veröffentlichtes Release');
});

it('explains clearly when GitHub limits the requests', function (): void {
    releaseHttp();
    Http::fake(['api.github.com/*' => Http::response([], 403)]);
    $admin = releaseUser('management');

    $this->actingAs($admin)->post(route('administration.update.release.check'))->assertRedirect(route('administration.update.index'));
    $this->actingAs($admin)->get(route('administration.update.index'))->assertSee('begrenzt gerade die Abfragen');
});

it('explains clearly when GitHub cannot be reached', function (): void {
    releaseHttp();
    Http::fake(['api.github.com/*' => static fn () => throw new ConnectionException('timeout')]);
    $admin = releaseUser('management');

    $this->actingAs($admin)->post(route('administration.update.release.check'))->assertRedirect(route('administration.update.index'));
    $this->actingAs($admin)->get(route('administration.update.index'))->assertSee('nicht erreichbar');
});

it('refuses to fetch a release that was not looked up first and keeps the pages away from others', function (): void {
    releaseFake('v9.9.9');

    $this->actingAs(releaseUser('management'))->post(route('administration.update.release.fetch'), ['tag' => 'v9.9.9'])->assertSessionHasErrors('release');
    $this->actingAs(releaseUser('management'))->post(route('administration.update.release.fetch'), ['tag' => '../../x'])->assertSessionHasErrors('tag');

    foreach (['staff', 'student_ag_extended'] as $role) {
        $this->actingAs(releaseUser($role))->post(route('administration.update.release.check'))->assertForbidden();
        $this->actingAs(releaseUser($role))->post(route('administration.update.release.fetch'), ['tag' => 'v9.9.9'])->assertForbidden();
    }
});

it('fetches a new release at night by itself only when both switches are on, and then applies it', function (): void {
    releaseFake('v9.9.9');
    $updates = app(UpdateManager::class);

    // Nur „automatisch einspielen“: Es wird nichts geholt.
    $updates->setAuto(true);
    $this->artisan('system:update-nightly')->assertSuccessful();
    expect(File::glob($this->root.'/updates/*.zip'))->toBe([])->and($updates->pending())->toBeNull();

    // Beide Schalter: Der Server holt das Release und spielt es ein; der Cron schließt ab.
    $updates->setAutoDownload(true);
    $this->artisan('system:update-nightly')->expectsOutputToContain('von GitHub geholt')->assertSuccessful();
    expect(is_file($this->root.'/updates/bibliocollect-v9.9.9.zip'))->toBeTrue()->and($updates->pending())->not->toBeNull();

    UpdateManager::$applied = false;
    $this->artisan('app:cron')->assertSuccessful();
    expect(trim((string) file_get_contents($this->root.'/target/VERSION')))->toBe('v9.9.9');
});

it('does not fetch a release that is not newer than the installed version', function (): void {
    releaseFake('v0.68.1');
    $updates = app(UpdateManager::class);
    $updates->setAuto(true);
    $updates->setAutoDownload(true);

    $this->artisan('system:update-nightly')->assertSuccessful();

    expect(File::glob($this->root.'/updates/*.zip'))->toBe([]);
});

it('switches the automatic fetch off together with the automatic update', function (): void {
    $admin = releaseUser('management');
    $updates = app(UpdateManager::class);

    $this->actingAs($admin)->post(route('administration.update.auto'), ['auto' => '1', 'auto_download' => '1'])->assertSessionHas('update_success');
    expect($updates->autoEnabled())->toBeTrue()->and($updates->autoDownloadEnabled())->toBeTrue();

    $this->actingAs($admin)->post(route('administration.update.auto'), ['auto' => '0', 'auto_download' => '1']);
    expect($updates->autoEnabled())->toBeFalse()->and($updates->autoDownloadEnabled())->toBeFalse();
});
