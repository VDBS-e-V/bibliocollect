<?php

declare(strict_types=1);

use App\Foundation\Support\CsvExport;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('runs in local time so that schedule and display agree', function (): void {
    expect(config('app.timezone'))->toBe('Europe/Berlin')->and(config('foundation.business_timezone'))->toBe('Europe/Berlin');

    $event = collect(app(Schedule::class)->events())->first(fn ($event): bool => $event->description === 'reminders:send');

    expect($event)->not->toBeNull()
        ->and($event->nextRunDate()->timezone('Europe/Berlin')->format('H:i'))->toBe('07:00');
});

it('trusts the proxy headers of the host', function (): void {
    Route::get('/_proxy-probe', static fn (): string => request()->ip().'|'.(request()->isSecure() ? 'https' : 'http'));

    $this->withHeaders(['X-Forwarded-For' => '203.0.113.9', 'X-Forwarded-Proto' => 'https'])
        ->get('/_proxy-probe')
        ->assertOk()
        ->assertSee('203.0.113.9|https', false);
});

it('limits attempts to claim an account', function (): void {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('identity.claim.store'), ['library_number' => '999999', 'code' => 'FALSCHERCODE', 'name' => 'A', 'email' => 'a'.$attempt.'@example.org', 'password' => 'x', 'password_confirmation' => 'x']);
    }

    $this->post(route('identity.claim.store'), ['library_number' => '999999', 'code' => 'FALSCHERCODE'])->assertStatus(429);
});

it('keeps spreadsheet formulas out of CSV exports', function (): void {
    expect(CsvExport::cell('=HYPERLINK("http://x")'))->toBe("'=HYPERLINK(\"http://x\")")
        ->and(CsvExport::cell('+49 123'))->toBe("'+49 123")
        ->and(CsvExport::cell('@summe'))->toBe("'@summe")
        ->and(CsvExport::cell('-Titel'))->toBe("'-Titel")
        ->and(CsvExport::cell('-5'))->toBe('-5')
        ->and(CsvExport::cell('Normaler Titel'))->toBe('Normaler Titel')
        ->and(CsvExport::cell(12))->toBe(12);

    $handle = fopen('php://memory', 'w+');
    CsvExport::put($handle, ['=1+1', 'Text', 3]);
    rewind($handle);

    expect(trim((string) stream_get_contents($handle)))->toBe("'=1+1;Text;3");
});

it('blocks scripts in upload folders and hides internal areas from search engines', function (): void {
    $htaccess = (string) file_get_contents(public_path('.htaccess'));
    $robots = (string) file_get_contents(public_path('robots.txt'));

    expect($htaccess)->toContain('(covers|card-designs)')->and($htaccess)->toContain('[F,L]')
        ->and($robots)->toContain('Disallow: /verwaltung')->toContain('Disallow: /betrieb')->toContain('Disallow: /_');
});

it('warns in production about open setup token, insecure cookie and unlimited logs', function (): void {
    $this->app['env'] = 'production';
    config(['hosting.setup_token' => str_repeat('x', 30), 'session.secure' => false, 'logging.default' => 'single', 'app.debug' => false]);

    $this->artisan('app:doctor')
        ->expectsOutputToContain('SETUP_TOKEN')
        ->expectsOutputToContain('Sitzungs-Cookie')
        ->expectsOutputToContain('Datenbank-Treiber')
        ->expectsOutputToContain('Zeitzone');
});
