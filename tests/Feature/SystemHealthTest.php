<?php

declare(strict_types=1);

use App\Foundation\Console\CronCommand;
use App\Foundation\Mail\AlertMail;
use App\Foundation\Models\SystemErrorEvent;
use App\Foundation\Support\SystemErrorLog;
use App\Foundation\Support\SystemHealth;
use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Eine übrig gebliebene Sicherung eines anderen Tests würde die Prüfung „Sicherung fehlt“ verfälschen.
    File::deleteDirectory(storage_path('app/backups'));
    Cache::flush();
});

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/backups'));
    Cache::flush();
});

function healthUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function healthState(string $key): string
{
    return collect(app(SystemHealth::class)->snapshot()['checks'])->firstWhere('key', $key)['state'];
}

final class HealthFailingJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        throw new RuntimeException('Der Job ist absichtlich gescheitert.');
    }
}

it('reports the state of cron, queue and backup', function (): void {
    expect(healthState('cron'))->toBe('warn');

    Cache::forever(CronCommand::HEARTBEAT_KEY, now()->subMinutes(2)->toIso8601String());
    expect(healthState('cron'))->toBe('ok');

    Cache::forever(CronCommand::HEARTBEAT_KEY, now()->subMinutes(40)->toIso8601String());
    expect(healthState('cron'))->toBe('warn');

    Cache::forever(CronCommand::HEARTBEAT_KEY, now()->subHours(5)->toIso8601String());
    expect(healthState('cron'))->toBe('fail');

    expect(healthState('backup'))->toBe('warn');
    File::ensureDirectoryExists(storage_path('app/backups'));
    file_put_contents(storage_path('app/backups/datenbank-neu.sqlite'), 'x');
    expect(healthState('backup'))->toBe('ok');
    touch(storage_path('app/backups/datenbank-neu.sqlite'), time() - 80 * 3600);
    expect(healthState('backup'))->toBe('fail');

    expect(healthState('database'))->toBe('ok')->and(healthState('queue'))->toBe('ok');
    DB::table('failed_jobs')->insert(['uuid' => 'abc', 'connection' => 'database', 'queue' => 'default', 'payload' => '{}', 'exception' => 'x', 'failed_at' => now()]);
    expect(healthState('queue'))->toBe('warn');
});

it('gives monitoring a json status with the right key and answers 503 when something is broken', function (): void {
    config(['hosting.cron_token' => 'ein-ausreichend-langes-status-token']);
    Cache::forever(CronCommand::HEARTBEAT_KEY, now()->subMinutes(1)->toIso8601String());
    File::ensureDirectoryExists(storage_path('app/backups'));
    file_put_contents(storage_path('app/backups/neu.sqlite'), 'x');

    $this->get('/_status')->assertNotFound();
    $this->withHeader('X-Api-Key', 'falsch-falsch-falsch-falsch-falsch')->get('/_status')->assertNotFound();

    $ok = $this->withHeader('X-Api-Key', 'ein-ausreichend-langes-status-token')->get('/_status')->assertOk();
    expect($ok->json('status'))->toBeIn(['ok', 'warn'])->and(collect($ok->json('checks'))->pluck('key')->all())->toContain('cron', 'queue', 'backup', 'database', 'errors', 'mail', 'disk');

    $this->get('/_status/ein-ausreichend-langes-status-token')->assertOk();

    Cache::forever(CronCommand::HEARTBEAT_KEY, now()->subHours(6)->toIso8601String());
    $this->withHeader('X-Api-Key', 'ein-ausreichend-langes-status-token')->get('/_status')->assertStatus(503)->assertJsonPath('status', 'fail');

    // Ein eigener Status-Schlüssel hat Vorrang.
    config(['hosting.status_token' => 'ein-anderer-status-schluessel-123']);
    $this->withHeader('X-Api-Key', 'ein-ausreichend-langes-status-token')->get('/_status')->assertNotFound();
});

it('records unexpected errors once per kind, redacts database errors and ignores expected ones', function (): void {
    $log = app(SystemErrorLog::class);

    $thrown = static fn (): RuntimeException => new RuntimeException('Etwas ist kaputt');
    $first = $thrown();

    $log->record($first);
    $log->record($first);
    $log->record(new NotFoundHttpException);
    $log->record(ValidationException::withMessages(['a' => 'b']));
    $log->record(new HttpException(503, 'Wartung'));
    $log->record(new QueryException('sqlite', 'select * from users where email = ?', ['geheim@example.org'], new Exception('SQLSTATE geheim@example.org')));

    $events = SystemErrorEvent::query()->orderBy('id')->get();

    expect($events)->toHaveCount(3)
        ->and($events[0]->class)->toBe(RuntimeException::class)
        ->and($events[0]->occurrences)->toBe(2)
        ->and($events[0]->message)->toBe('Etwas ist kaputt')
        ->and($events[2]->message)->toBe('Datenbankfehler (Einzelheiten im Log)')
        ->and($events->pluck('message')->implode(' '))->not->toContain('geheim@example.org');

    expect(healthState('errors'))->toBe('warn');
});

it('catches errors thrown on a page, without storing request data', function (): void {
    Route::get('/_test-boom', static fn () => throw new RuntimeException('Seite kaputt'));

    $this->get('/_test-boom?passwort=geheim123')->assertStatus(500);

    $event = SystemErrorEvent::query()->firstOrFail();
    expect($event->message)->toBe('Seite kaputt')->and($event->method)->toBe('GET')->and($event->path)->toBe('/_test-boom')
        ->and(json_encode($event->getAttributes()))->not->toContain('geheim123');
});

it('mails the administration once per throttle window and stays quiet without an address', function (): void {
    Mail::fake();
    $log = app(SystemErrorLog::class);

    // Ohne Adresse: nur festhalten.
    config(['hosting.alert_email' => null]);
    $log->record(new RuntimeException('Ohne Adresse'));
    Mail::assertNothingSent();

    config(['hosting.alert_email' => 'admin@example.invalid']);
    $same = new RuntimeException('Mit Adresse');
    $log->record($same);
    $log->record($same);
    $log->record(new LogicException('Anderer Fehler'));

    Mail::assertSent(AlertMail::class, 2);
    Mail::assertSent(AlertMail::class, static fn (AlertMail $mail): bool => $mail->hasTo('admin@example.invalid') && str_contains($mail->alertSubject, 'RuntimeException'));
});

it('records failed jobs and tells the administration', function (): void {
    Mail::fake();
    config(['hosting.alert_email' => 'admin@example.invalid']);

    try {
        HealthFailingJob::dispatch();
    } catch (Throwable) {
        // Bei „sync“ wirft der Job nach dem Ereignis weiter.
    }

    $event = SystemErrorEvent::query()->firstOrFail();
    expect($event->kind)->toBe('job')->and($event->class)->toContain('HealthFailingJob')->and($event->message)->toBe('Der Job ist absichtlich gescheitert.');
    Mail::assertSent(AlertMail::class, static fn (AlertMail $mail): bool => str_contains($mail->alertSubject, 'Job fehlgeschlagen'));
});

it('warns when the cron stood still and runs again', function (): void {
    Mail::fake();
    config(['hosting.alert_email' => 'admin@example.invalid']);
    Cache::forever(CronCommand::HEARTBEAT_KEY, now()->subMinutes(45)->toIso8601String());

    $this->artisan('app:cron', ['--queue-seconds' => 5])->assertSuccessful();
    Mail::assertSent(AlertMail::class, static fn (AlertMail $mail): bool => str_contains($mail->alertSubject, 'Cron') && str_contains(implode(' ', $mail->lines), '45 Minuten'));

    // Direkt danach ist alles wieder in Ordnung: keine zweite Meldung.
    $this->artisan('app:cron', ['--queue-seconds' => 5])->assertSuccessful();
    Mail::assertSent(AlertMail::class, 1);
});

it('shows the system state to the administration and lets it send a test message', function (): void {
    Mail::fake();
    config(['hosting.alert_email' => 'admin@example.invalid']);
    app(SystemErrorLog::class)->record(new RuntimeException('Sichtbarer Fehler'));

    foreach (['management', 'technical_admin'] as $role) {
        $this->actingAs(healthUser($role))->get(route('administration.system.index'))->assertOk()->assertSee('Gesamtzustand')->assertSee('Cron (Zeitplan und Warteschlange)')->assertSee('Sichtbarer Fehler')->assertSee('admin@example.invalid');
    }

    $this->actingAs(healthUser('staff'))->get(route('administration.system.index'))->assertForbidden();
    $this->actingAs(healthUser('student_ag_extended'))->get(route('administration.system.index'))->assertForbidden();

    $this->actingAs(healthUser('management'))->post(route('administration.system.test-alert'))->assertRedirect(route('administration.system.index'))->assertSessionHas('system_success');
    Mail::assertSent(AlertMail::class, static fn (AlertMail $mail): bool => $mail->alertSubject === 'Testmeldung');

    config(['hosting.alert_email' => null]);
    $this->actingAs(healthUser('management'))->post(route('administration.system.test-alert'))->assertSessionHas('system_error');
    $this->actingAs(healthUser('management'))->get(route('administration.system.index'))->assertSee('ALERT_EMAIL');
});

it('prunes old error entries', function (): void {
    SystemErrorEvent::query()->create(['fingerprint' => 'a', 'class' => 'X', 'message' => 'alt', 'first_seen_at' => now()->subDays(40), 'last_seen_at' => now()->subDays(40)]);
    SystemErrorEvent::query()->create(['fingerprint' => 'b', 'class' => 'Y', 'message' => 'neu', 'first_seen_at' => now()->subDays(2), 'last_seen_at' => now()->subDays(2)]);

    expect(app(SystemErrorLog::class)->prune(30))->toBe(1)->and(SystemErrorEvent::query()->pluck('message')->all())->toBe(['neu']);
});

it('lists the scheduled tasks on the system page and runs one of them once on demand', function (): void {
    $admin = healthUser('technical_admin');
    SystemErrorEvent::query()->create([
        'fingerprint' => str_repeat('b', 64), 'kind' => 'request', 'class' => RuntimeException::class, 'message' => 'Alt',
        'occurrences' => 1, 'first_seen_at' => now()->subDays(40), 'last_seen_at' => now()->subDays(40),
    ]);

    $this->actingAs($admin)->get(route('administration.system.index'))
        ->assertOk()
        ->assertSee('Zeitplan-Aufgaben')
        ->assertSee('reminders:send')
        ->assertSee('backup:database')
        ->assertSee('Cron-Lauf jetzt auslösen');

    $this->post(route('administration.system.run-job', ['job' => 'system:prune-errors']))
        ->assertRedirect(route('administration.system.index'))
        ->assertSessionHas('system_success', static fn (string $text): bool => str_contains($text, 'system:prune-errors') && str_contains($text, 'durchgelaufen'));

    // Die Aufgabe hat wirklich gearbeitet und der Lauf steht im Protokoll.
    expect(SystemErrorEvent::query()->where('message', 'Alt')->exists())->toBeFalse();
    expect(AuditEvent::query()->where('action', 'system.job.run')->count())->toBe(1);
});

it('runs a cron pass from the system page and leaves the heartbeat', function (): void {
    $admin = healthUser('technical_admin');

    $this->actingAs($admin)->post(route('administration.system.run-cron'))
        ->assertRedirect(route('administration.system.index'))
        ->assertSessionHas('system_success');

    expect(Cache::get(CronCommand::HEARTBEAT_KEY))->not->toBeNull();
    expect(AuditEvent::query()->where('action', 'system.cron.run')->count())->toBe(1);
});

it('rejects unknown tasks and keeps the task buttons away from other roles', function (): void {
    $admin = healthUser('technical_admin');

    $this->actingAs($admin)->post(route('administration.system.run-job', ['job' => 'gibt:es-nicht']))->assertNotFound();
    $this->post(route('administration.system.run-job', ['job' => 'rm -rf']))->assertNotFound();

    foreach (['staff', 'student_ag_basic', 'teacher'] as $role) {
        $this->actingAs(healthUser($role))->post(route('administration.system.run-job', ['job' => 'reminders:send']))->assertForbidden();
        $this->actingAs(healthUser($role))->post(route('administration.system.run-cron'))->assertForbidden();
    }
});
