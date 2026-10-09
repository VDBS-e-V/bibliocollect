<?php

declare(strict_types=1);

use App\Foundation\Update\UpdateException;
use App\Foundation\Update\UpdateManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->root = sys_get_temp_dir().'/bc-update-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($this->root.'/updates');
    File::ensureDirectoryExists($this->root.'/target/storage');
    File::ensureDirectoryExists($this->root.'/target/public/covers');
    File::put($this->root.'/target/.env', 'APP_KEY=geheim');
    File::put($this->root.'/target/VERSION', 'v0.1.0');
    File::put($this->root.'/target/app.txt', 'alt');
    File::put($this->root.'/target/public/covers/a.jpg', 'cover');
    File::ensureDirectoryExists($this->root.'/target/public/build');
    File::put($this->root.'/target/public/build/old.js', 'alt');
    config(['foundation.update.directory' => $this->root.'/updates', 'foundation.update.target' => $this->root.'/target', 'foundation.update.backup' => false]);
});

afterEach(function (): void {
    Artisan::call('up');
    UpdateManager::$applied = false;
    File::deleteDirectory($this->root);
});

/**
 * @param  array<string, string>  $extra
 * @param  list<string>  $without
 */
function updateZip(string $path, string $version = 'v0.2.0', array $extra = [], array $without = []): string
{
    $files = array_merge([
        'artisan' => '<?php',
        'composer.json' => '{}',
        'bootstrap/app.php' => '<?php',
        'vendor/autoload.php' => '<?php',
        'public/index.php' => '<?php',
        'VERSION' => $version,
        'app.txt' => 'neu',
        'public/build/new.js' => 'neu',
        '.env' => 'APP_KEY=ueberschrieben',
        'storage/logs/x.log' => 'x',
        'public/covers/b.jpg' => 'fremd',
    ], $extra);

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($files as $name => $content) {
        if (! in_array($name, $without, true)) {
            $zip->addFromString($name, $content);
        }
    }

    $zip->close();

    return $path;
}

it('lists and checks packages and compares the versions', function (): void {
    $updates = app(UpdateManager::class);
    updateZip($this->root.'/updates/neu.zip', 'v0.2.0');
    updateZip($this->root.'/updates/alt.zip', 'v0.0.9');
    updateZip($this->root.'/updates/kaputt.zip', 'v0.3.0', [], ['artisan']);

    $byName = collect($updates->packages())->keyBy('name');

    expect($updates->currentVersion())->toBe('v0.1.0')
        ->and($byName['neu.zip']['version'])->toBe('v0.2.0')->and($byName['neu.zip']['newer'])->toBeTrue()
        ->and($byName['alt.zip']['newer'])->toBeFalse()
        ->and($byName['kaputt.zip']['error'])->toContain('artisan')
        ->and($updates->newestUsable())->toBe('neu.zip')
        ->and($updates->compare('v0.49.0-3-gabc123', 'v0.49.0'))->toBeTrue()
        ->and($updates->compare('v0.49.0', 'v0.49.0'))->toBeFalse()
        ->and($updates->compare('unbekannt', 'v0.49.0'))->toBeNull();
});

it('refuses packages with unsafe file names and files that are not packages', function (): void {
    $updates = app(UpdateManager::class);
    updateZip($this->root.'/updates/slip.zip', 'v0.2.0', ['../boese.php' => 'x']);
    File::put($this->root.'/updates/text.zip', 'kein zip');

    expect(fn () => $updates->inspect($this->root.'/updates/slip.zip'))->toThrow(UpdateException::class, 'unzulässigen')
        ->and(fn () => $updates->inspect($this->root.'/updates/text.zip'))->toThrow(UpdateException::class, 'kein lesbares');
});

it('applies a package in maintenance mode, keeps protected files and finishes with the key', function (): void {
    $updates = app(UpdateManager::class);
    updateZip($this->root.'/updates/neu.zip', 'v0.2.0');

    $token = $updates->apply('neu.zip');

    expect(app()->isDownForMaintenance())->toBeTrue()
        ->and(File::get($this->root.'/target/app.txt'))->toBe('neu')
        ->and(File::get($this->root.'/target/.env'))->toBe('APP_KEY=geheim')
        ->and(File::exists($this->root.'/target/storage/logs/x.log'))->toBeFalse()
        ->and(File::exists($this->root.'/target/public/covers/b.jpg'))->toBeFalse()
        ->and(File::get($this->root.'/target/public/covers/a.jpg'))->toBe('cover')
        ->and(File::exists($this->root.'/target/public/build/old.js'))->toBeFalse()
        ->and(File::get($this->root.'/target/public/build/new.js'))->toBe('neu')
        ->and(UpdateManager::$applied)->toBeTrue()
        ->and($updates->pending()['to'])->toBe('v0.2.0');

    // Solange ein Update wartet, kein zweites; falscher Schlüssel schließt nichts ab.
    expect(fn () => $updates->apply('neu.zip'))->toThrow(UpdateException::class, 'wartet noch')
        ->and(fn () => $updates->finish(str_repeat('x', 40)))->toThrow(UpdateException::class, 'Schlüssel');

    $result = $updates->finish($token);

    expect($result['ok'])->toBeTrue()
        ->and(app()->isDownForMaintenance())->toBeFalse()
        ->and($updates->pending())->toBeNull()
        ->and($updates->lastResult()['message'])->toContain('v0.2.0')
        ->and($updates->currentVersion())->toBe('v0.2.0')
        ->and(File::exists($this->root.'/updates/done/neu.zip'))->toBeTrue()
        ->and(fn () => $updates->finish(null))->toThrow(UpdateException::class, 'wartet kein');
});

it('does not apply an older package without explicit consent', function (): void {
    $updates = app(UpdateManager::class);
    updateZip($this->root.'/updates/alt.zip', 'v0.0.9');

    expect(fn () => $updates->apply('alt.zip'))->toThrow(UpdateException::class, 'nicht neuer')
        ->and(app()->isDownForMaintenance())->toBeFalse()
        ->and(File::get($this->root.'/target/app.txt'))->toBe('alt');

    $updates->apply('alt.zip', true);
    expect(File::get($this->root.'/target/app.txt'))->toBe('neu');
});

it('applies the newest package at night only when switched on and lets the cron finish it', function (): void {
    $updates = app(UpdateManager::class);
    updateZip($this->root.'/updates/neu.zip', 'v0.2.0');

    $this->artisan('system:update-nightly')->assertSuccessful();
    expect($updates->pending())->toBeNull();

    $updates->setAuto(true);
    $this->artisan('system:update-nightly')->assertSuccessful();
    expect($updates->pending())->not->toBeNull()->and(app()->isDownForMaintenance())->toBeTrue();

    // Der nächste Cron-Aufruf schließt ab, bevor etwas anderes läuft.
    UpdateManager::$applied = false;
    $this->artisan('app:cron')->expectsOutputToContain('v0.2.0')->assertSuccessful();
    expect($updates->pending())->toBeNull()->and(app()->isDownForMaintenance())->toBeFalse();
});

it('keeps the finish address reachable while the site is in maintenance mode', function (): void {
    $updates = app(UpdateManager::class);
    updateZip($this->root.'/updates/neu.zip', 'v0.2.0');
    $token = $updates->apply('neu.zip');

    $this->get('/katalog')->assertStatus(503);
    $this->get('/_update/abschluss/'.str_repeat('f', 40))->assertStatus(500)->assertSee('Update nicht abgeschlossen');
    $this->get('/_update/abschluss/'.$token)->assertRedirect(route('administration.update.index'));
    expect(app()->isDownForMaintenance())->toBeFalse();
});
