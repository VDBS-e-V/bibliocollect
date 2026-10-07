<?php

declare(strict_types=1);

use App\Foundation\Environment\EnvSynchronizer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->envRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'bc-env-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($this->envRoot);
    config([
        'foundation.environment.root' => $this->envRoot,
        'foundation.environment.template' => '.env.example',
        'foundation.environment.targets' => ['.env', '.env.testing'],
        'foundation.environment.backup_path' => '.foundation/env-backups',
    ]);

    file_put_contents($this->envRoot.'/.env.example', "# App\nAPP_NAME=Demo\nAPP_KEY=\n\n# Datenbank\nDB_HOST=127.0.0.1\nDB_PASSWORD=\n");
});

afterEach(function (): void {
    File::deleteDirectory($this->envRoot);
});

it('registers all env commands', function (): void {
    $commands = array_keys(Artisan::all());

    expect($commands)->toContain('env:sync', 'env:check', 'env:diff', 'env:backup', 'env:restore');
});

it('diffs keys only and keeps extras', function (): void {
    $sync = new EnvSynchronizer;
    $diff = $sync->diff("A=1\nB=2\n", "B=9\nC=3\n");

    expect($diff)->toBe(['missing' => ['A'], 'extra' => ['C']]);
});

it('synchronizes by template structure and keeps existing values', function (): void {
    $sync = new EnvSynchronizer;
    $result = $sync->synchronize("# Kopf\nA=1\nB=2\n", "B=geheim\nC=3\n");

    expect($result['missing'])->toBe(['A'])
        ->and($result['extra'])->toBe(['C'])
        ->and($result['changed'])->toBe([])
        ->and($result['content'])->toBe("# Kopf\nA=1\nB=geheim\n\n# Weitere Einträge (nicht in der Vorlage)\nC=3\n");

    $forced = $sync->synchronize("A=1\nB=2\n", "A=alt\nB=2\nC=3\n", force: true, prune: true);
    expect($forced['changed'])->toBe(['A'])->and($forced['content'])->toBe("A=1\nB=2\n");

    // Zweiter Lauf ändert nichts mehr.
    expect($sync->synchronize("# Kopf\nA=1\nB=2\n", $result['content'])['content'])->toBe($result['content']);
});

it('adds missing keys on sync without replacing values', function (): void {
    file_put_contents($this->envRoot.'/.env', "APP_NAME=Mein Projekt\nEXTRA_KEY=bleibt\n");

    $this->artisan('env:sync', ['--target' => ['.env']])->assertSuccessful();

    $content = file_get_contents($this->envRoot.'/.env');
    expect($content)->toContain('APP_NAME=Mein Projekt')->toContain('APP_KEY=')->toContain('DB_HOST=127.0.0.1')->toContain('EXTRA_KEY=bleibt')->toContain('# Datenbank');
    expect(is_dir($this->envRoot.'/.foundation/env-backups'))->toBeFalse();
});

it('creates missing default targets', function (): void {
    $this->artisan('env:sync')->assertSuccessful();

    expect(is_file($this->envRoot.'/.env'))->toBeTrue()->and(is_file($this->envRoot.'/.env.testing'))->toBeTrue();
});

it('writes nothing on a dry run and never prints values', function (): void {
    file_put_contents($this->envRoot.'/.env', "APP_NAME=TopSecretValue\n");

    $this->artisan('env:sync', ['--target' => ['.env'], '--dry-run' => true, '--force' => true])
        ->expectsOutputToContain('APP_KEY')
        ->doesntExpectOutputToContain('TopSecretValue')
        ->assertSuccessful();

    expect(file_get_contents($this->envRoot.'/.env'))->toBe("APP_NAME=TopSecretValue\n");
});

it('needs confirmation for destructive modes and backs up first', function (): void {
    file_put_contents($this->envRoot.'/.env', "APP_NAME=Alt\nOLD_KEY=x\n");

    $this->artisan('env:sync', ['--target' => ['.env'], '--force' => true])->expectsConfirmation('Das kann vorhandene Werte oder Schlüssel dauerhaft verändern. Fortfahren?', 'no')->assertFailed();
    expect(file_get_contents($this->envRoot.'/.env'))->toBe("APP_NAME=Alt\nOLD_KEY=x\n");

    $this->artisan('env:sync', ['--target' => ['.env'], '--force' => true, '--prune' => true, '--yes' => true])->assertSuccessful();

    expect(file_get_contents($this->envRoot.'/.env'))->toContain('APP_NAME=Demo')->not->toContain('OLD_KEY');

    $backups = glob($this->envRoot.'/.foundation/env-backups/*.bak');
    expect($backups)->toHaveCount(1)->and(file_get_contents($backups[0]))->toBe("APP_NAME=Alt\nOLD_KEY=x\n");
});

it('refuses destructive modes without a terminal unless --yes is given', function (): void {
    file_put_contents($this->envRoot.'/.env', "APP_NAME=Alt\n");

    $this->artisan('env:sync', ['--target' => ['.env'], '--prune' => true, '--no-interaction' => true])->assertFailed();
    expect(file_get_contents($this->envRoot.'/.env'))->toBe("APP_NAME=Alt\n");
});

it('checks missing keys with a failure code and never shows values', function (): void {
    file_put_contents($this->envRoot.'/.env', "APP_NAME=TopSecretValue\nEXTRA=1\n");
    file_put_contents($this->envRoot.'/.env.testing', "APP_NAME=a\nAPP_KEY=\nDB_HOST=h\nDB_PASSWORD=\n");

    $this->artisan('env:check')->expectsOutputToContain('APP_KEY')->doesntExpectOutputToContain('TopSecretValue')->assertFailed();

    file_put_contents($this->envRoot.'/.env', "APP_NAME=a\nAPP_KEY=\nDB_HOST=h\nDB_PASSWORD=\nEXTRA=1\n");
    $this->artisan('env:check')->assertSuccessful();
});

it('diffs a target by key names', function (): void {
    file_put_contents($this->envRoot.'/.env', "APP_NAME=Geheim\nEXTRA=1\n");

    $this->artisan('env:diff')->expectsOutputToContain('Missing')->expectsOutputToContain('Extra')->doesntExpectOutputToContain('Geheim')->assertFailed();
    $this->artisan('env:diff', ['target' => '.env.fehlt'])->assertFailed();
    $this->artisan('env:diff', ['target' => '../.env'])->assertFailed();
});

it('backs up timestamped, skips missing targets and restores the latest after confirmation', function (): void {
    file_put_contents($this->envRoot.'/.env', "APP_NAME=Eins\n");

    $this->artisan('env:backup')->expectsOutputToContain('.env.testing')->assertSuccessful();
    $backups = glob($this->envRoot.'/.foundation/env-backups/*.bak');
    expect($backups)->toHaveCount(1)->and(basename($backups[0]))->toMatch('/^env\.\d{8}-\d{6}\.bak$/');

    file_put_contents($this->envRoot.'/.env', "APP_NAME=Zwei\n");

    $this->artisan('env:restore')->expectsConfirmation('.env aus dieser Sicherung wiederherstellen?', 'no')->assertFailed();
    expect(file_get_contents($this->envRoot.'/.env'))->toBe("APP_NAME=Zwei\n");

    $this->artisan('env:restore', ['--yes' => true])->assertSuccessful();
    expect(file_get_contents($this->envRoot.'/.env'))->toBe("APP_NAME=Eins\n");

    // Der Stand vor dem Restore wurde zusätzlich gesichert.
    expect(glob($this->envRoot.'/.foundation/env-backups/*.bak'))->toHaveCount(2);
});

it('restores a named backup only by file name and rejects unsafe paths', function (): void {
    file_put_contents($this->envRoot.'/.env', "APP_NAME=Eins\n");
    $this->artisan('env:backup', ['--target' => ['.env']])->assertSuccessful();
    $name = basename(glob($this->envRoot.'/.foundation/env-backups/*.bak')[0]);

    $this->artisan('env:restore', ['backup' => '../../.env', '--yes' => true])->assertFailed();
    $this->artisan('env:restore', ['backup' => '..\\..\\.env', '--yes' => true])->assertFailed();
    $this->artisan('env:restore', ['backup' => 'gibtsnicht.bak', '--yes' => true])->assertFailed();
    $this->artisan('env:restore', ['backup' => $name, '--target' => '../.env', '--yes' => true])->assertFailed();
    $this->artisan('env:restore', ['backup' => $name, '--target' => 'C:\\Windows\\x.env', '--yes' => true])->assertFailed();
    $this->artisan('env:restore', ['backup' => $name, '--target' => '/etc/x', '--yes' => true])->assertFailed();

    $this->artisan('env:restore', ['backup' => $name, '--yes' => true])->assertSuccessful();
});

it('refuses to restore without a terminal unless --yes is given', function (): void {
    file_put_contents($this->envRoot.'/.env', "APP_NAME=Eins\n");
    $this->artisan('env:backup', ['--target' => ['.env']])->assertSuccessful();
    file_put_contents($this->envRoot.'/.env', "APP_NAME=Zwei\n");

    $this->artisan('env:restore', ['--no-interaction' => true])->assertFailed();
    expect(file_get_contents($this->envRoot.'/.env'))->toBe("APP_NAME=Zwei\n");
});

it('ignores env backups in git', function (): void {
    expect(file_get_contents(base_path('.gitignore')))->toContain('/.foundation/env-backups/');
});
