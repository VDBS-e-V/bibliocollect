<?php

declare(strict_types=1);

use App\Foundation\Mail\AlertMail;
use App\Models\User;
use App\Modules\Circulation\Enums\WishStatus;
use App\Modules\Circulation\Mail\WishStatusMail;
use App\Modules\Circulation\Models\BookWish;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('sends every mail in the VDBS design with logo, brand colours and legal links', function (): void {
    $reset = (string) (new ResetPassword('abc123'))->toMail(new User(['name' => 'Mia', 'email' => 'mia@example.invalid']))->render();
    $wish = (new WishStatusMail(new BookWish(['title' => 'Der Grüffelo', 'status' => WishStatus::Ordered, 'answer' => 'Nächste Woche.', 'contact_name' => 'Mia'])))->render();
    $alert = (new AlertMail('Betreff', ['Eine Zeile']))->render();

    foreach ([$reset, $wish, $alert] as $html) {
        expect($html)->toContain('brand/vdbs/mail-logo.png')
            ->toContain('#58275a')
            ->toContain('#2dc08e')
            ->toContain('Eine Anwendung des VDBS e. V.')
            ->toContain('/impressum')->toContain('/datenschutz')->toContain('/barrierefreiheit');
    }

    expect($wish)->toContain('Anmerkung der Bibliothek')->and($reset)->toContain('Passwort festlegen');
    expect(file_exists(public_path('brand/vdbs/mail-logo.png')))->toBeTrue();
});

it('shows a preview of every mail to the administration without sending anything', function (): void {
    Mail::fake();
    $admin = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($admin, 'management');
    $student = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($student, 'student');

    $this->actingAs($student)->get(route('administration.mail-preview'))->assertForbidden();

    foreach (['passwort', 'bestaetigen', 'faellig', 'ueberfaellig', 'bereit', 'beleg', 'wunsch-angenommen', 'wunsch-bestellt', 'wunsch-da', 'wunsch-abgelehnt', 'betrieb'] as $key) {
        $page = $this->actingAs($admin)->get(route('administration.mail-preview', ['mail' => $key]))->assertOk();
        $page->assertSee('E-Mail-Vorschau')->assertSee('mail-logo.png', false)->assertSee('Betreff');
    }

    $this->actingAs($admin)->get(route('administration.mail-preview', ['mail' => 'beleg', 'breite' => 'handy']))->assertOk()->assertSee('B-2026-000123')->assertSee('bc-mailpreview__frame--narrow', false);
    $this->actingAs($admin)->get(route('administration.mail-preview', ['mail' => 'gibt-es-nicht']))->assertOk()->assertSee('Neues Passwort');

    Mail::assertNothingSent();
});

it('sends the previewed mail or all mails as a test to a chosen address', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now(), 'email' => 'verwaltung@example.org']);
    app(AssignRoleAction::class)->execute($admin, 'management');
    $transport = fn () => Mail::mailer('array')->getSymfonyTransport();

    $this->actingAs($admin)->get(route('administration.mail-preview'))->assertSee('verwaltung@example.org')->assertSee('Diese Mail schicken');

    $this->actingAs($admin)->post(route('administration.mail-preview.send'), ['mail' => 'beleg', 'an' => 'ich@example.org'])->assertSessionHas('mail_sent');
    expect($transport()->messages())->toHaveCount(1);
    $message = $transport()->messages()->first()->getOriginalMessage();
    expect($message->getSubject())->toStartWith('[Vorschau] Dein Beleg')
        ->and($message->getTo()[0]->getAddress())->toBe('ich@example.org')
        ->and($message->getHtmlBody())->toContain('B-2026-000123')->toContain('mail-logo.png');

    $this->actingAs($admin)->post(route('administration.mail-preview.send'), ['mail' => 'passwort', 'an' => 'ich@example.org', 'alle' => '1'])->assertSessionHas('mail_sent');
    expect($transport()->messages())->toHaveCount(12);

    $this->actingAs($admin)->post(route('administration.mail-preview.send'), ['mail' => 'passwort', 'an' => 'keine-adresse'])->assertSessionHasErrors('an');
});
