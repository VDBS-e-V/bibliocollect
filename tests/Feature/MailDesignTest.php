<?php

declare(strict_types=1);

use App\Foundation\Mail\AlertMail;
use App\Models\User;
use App\Modules\Circulation\Enums\WishStatus;
use App\Modules\Circulation\Mail\WishStatusMail;
use App\Modules\Circulation\Models\BookWish;
use Illuminate\Auth\Notifications\ResetPassword;

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
