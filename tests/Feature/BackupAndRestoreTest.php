<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    File::deleteDirectory(storage_path('app/backups'));
});

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/backups'));
});

function backupAdmin(string $role = 'technical_admin'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function restoreConnection(): ?string
{
    $config = config('database.connections.'.config('database.default'));

    if (! is_array($config) || ! in_array($config['driver'] ?? '', ['mysql', 'mariadb'], true)) {
        return null;
    }

    config(['database.connections.restore_probe' => array_merge($config, ['database' => env('DB_RESTORE_DATABASE', 'bibliocollect_restore')])]);

    try {
        DB::connection('restore_probe')->getPdo();
    } catch (Throwable) {
        return null;
    }

    return 'restore_probe';
}

it('lists, downloads and protects database backups on the system page', function (): void {
    File::ensureDirectoryExists(storage_path('app/backups'));
    file_put_contents(storage_path('app/backups/datenbank-2026-10-07_010000.sql.gz'), 'x');
    file_put_contents(storage_path('app/backups/geheim.txt'), 'nicht anzeigen');

    $admin = backupAdmin();

    $this->actingAs($admin)->get(route('administration.system.index'))
        ->assertOk()
        ->assertSee('Datensicherung')
        ->assertSee('datenbank-2026-10-07_010000.sql.gz')
        ->assertDontSee('geheim.txt');

    $this->get(route('administration.system.backup.download', ['file' => 'datenbank-2026-10-07_010000.sql.gz']))->assertOk();
    expect(AuditEvent::query()->where('action', 'system.backup.downloaded')->count())->toBe(1);

    $this->get(route('administration.system.backup.download', ['file' => 'geheim.txt']))->assertNotFound();
    $this->get(route('administration.system.backup.download', ['file' => 'datenbank-9999.sql.gz']))->assertNotFound();
    $this->get('/verwaltung/systemzustand/sicherung/..%2F..%2F.env')->assertNotFound();
});

it('keeps backups away from other roles', function (): void {
    File::ensureDirectoryExists(storage_path('app/backups'));
    file_put_contents(storage_path('app/backups/datenbank-2026-10-07_010000.sql.gz'), 'x');

    foreach (['staff', 'student_ag_basic', 'teacher'] as $role) {
        $user = backupAdmin($role);
        $this->actingAs($user)->get(route('administration.system.backup.download', ['file' => 'datenbank-2026-10-07_010000.sql.gz']))->assertForbidden();
        $this->actingAs($user)->post(route('administration.system.backup'))->assertForbidden();
    }
});

it('reports a failed manual backup instead of crashing', function (): void {
    // In der Testdatenbank (SQLite im Speicher, in einer Transaktion) kann VACUUM INTO nicht laufen.
    $this->actingAs(backupAdmin());

    $response = $this->post(route('administration.system.backup'));

    $response->assertRedirect(route('administration.system.index'));
    expect(session('system_error') ?? session('system_success'))->not->toBeNull();
})->skip(fn (): bool => DB::connection()->getDriverName() !== 'sqlite', 'Nur mit SQLite im Speicher aussagekräftig.');

it('restores a MySQL backup into an empty database without running migrations first', function (): void {
    $connection = restoreConnection();

    if ($connection === null) {
        $this->markTestSkipped('Keine zweite MySQL-Datenbank (DB_RESTORE_DATABASE) erreichbar.');
    }

    $user = User::factory()->create(['name' => "Zoë \"O'Brien\" \ Test\nZeile 2 😀", 'email' => 'sonderzeichen@example.org']);
    DB::table('system_error_events')->insert(['fingerprint' => str_repeat('c', 64), 'kind' => 'exception', 'class' => 'X', 'message' => "Text; mit Semikolon;\nund Zeilenumbruch", 'occurrences' => 3, 'first_seen_at' => '2026-10-01 10:00:00', 'last_seen_at' => '2026-10-02 11:30:00']);

    Artisan::call('backup:database');
    $file = collect(File::files(storage_path('app/backups')))->first(fn ($f): bool => str_ends_with($f->getFilename(), '.sql.gz'));
    expect($file)->not->toBeNull();

    $this->artisan('backup:restore', ['file' => $file->getPathname(), '--database' => $connection, '--yes' => true])->assertSuccessful();

    $restored = DB::connection($connection)->table('users')->where('email', 'sonderzeichen@example.org')->first();
    $event = DB::connection($connection)->table('system_error_events')->where('fingerprint', str_repeat('c', 64))->first();

    expect($restored)->not->toBeNull()
        ->and($restored->name)->toBe($user->name)
        ->and($event->message)->toBe("Text; mit Semikolon;\nund Zeilenumbruch")
        ->and((int) $event->occurrences)->toBe(3)
        ->and(DB::connection($connection)->table('migrations')->count())->toBe(DB::table('migrations')->count());

    // Der Dump ist eigenständig: Tabellen werden mit Struktur angelegt, eine zweite Wiederherstellung ersetzt alles.
    $this->artisan('backup:restore', ['file' => $file->getPathname(), '--database' => $connection, '--yes' => true])->assertSuccessful();
})->skip(fn (): bool => ! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true), 'Nur auf MySQL/MariaDB.');

it('asks before restoring and refuses unknown files', function (): void {
    $this->artisan('backup:restore', ['file' => 'gibt-es-nicht.sql.gz', '--yes' => true])->assertFailed();

    File::ensureDirectoryExists(storage_path('app/backups'));
    file_put_contents(storage_path('app/backups/datenbank-2026-10-07_020000.sql.gz'), 'x');

    $this->artisan('backup:restore', ['file' => 'datenbank-2026-10-07_020000.sql.gz', '--no-interaction' => true])->assertFailed();
});
