<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Foundation\Mail\AlertMail;
use App\Models\User;
use App\Modules\Circulation\Enums\WishStatus;
use App\Modules\Circulation\Mail\TransactionReceiptMail;
use App\Modules\Circulation\Mail\WishStatusMail;
use App\Modules\Circulation\Models\BookWish;
use App\Modules\Circulation\Models\LoanTransaction;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Reminders\Notifications\LibraryReminder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Vorschau aller E-Mails der Anwendung mit Beispielangaben. Es wird nichts verschickt und nichts gespeichert; die Beispieldaten sind erfunden.
 */
final class MailPreviewController
{
    /** @return array<string, array{label: string, group: string}> */
    private function catalog(): array
    {
        return [
            'passwort' => ['label' => 'Neues Passwort festlegen', 'group' => 'Onlinekonto'],
            'bestaetigen' => ['label' => 'E-Mail-Adresse bestätigen', 'group' => 'Onlinekonto'],
            'faellig' => ['label' => 'Rückgabe bald fällig', 'group' => 'Erinnerungen'],
            'ueberfaellig' => ['label' => 'Rückgabe überfällig', 'group' => 'Erinnerungen'],
            'bereit' => ['label' => 'Vorgemerkter Titel liegt bereit', 'group' => 'Erinnerungen'],
            'beleg' => ['label' => 'Beleg vom Tresen', 'group' => 'Ausleihe'],
            'wunsch-angenommen' => ['label' => 'Buchwunsch angenommen', 'group' => 'Buchwünsche'],
            'wunsch-bestellt' => ['label' => 'Buchwunsch bestellt', 'group' => 'Buchwünsche'],
            'wunsch-da' => ['label' => 'Buchwunsch erfüllt', 'group' => 'Buchwünsche'],
            'wunsch-abgelehnt' => ['label' => 'Buchwunsch abgelehnt (mit Anmerkung)', 'group' => 'Buchwünsche'],
            'betrieb' => ['label' => 'Betriebsmeldung an die Administration', 'group' => 'Betrieb'],
        ];
    }

    public function index(Request $request): Response
    {
        $catalog = $this->catalog();
        $key = (string) $request->query('mail', 'passwort');
        $key = isset($catalog[$key]) ? $key : 'passwort';
        $narrow = $request->query('breite') === 'handy';

        [$subject, $html] = $this->render($key);

        // Bilder zeigen auf die Adresse, unter der die Seite gerade geöffnet ist (lokal weicht sie oft von APP_URL ab).
        $html = str_replace(url('brand/'), $request->root().'/brand/', $html);

        return response()
            ->view('pages.surfaces.administration.mail.preview', [
                'catalog' => $catalog,
                'groups' => collect($catalog)->groupBy('group', true),
                'current' => $key,
                'subject' => $subject,
                'html' => $html,
                'narrow' => $narrow,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** @return array{0: string, 1: string} Betreff und HTML */
    private function render(string $key): array
    {
        $user = new User(['name' => 'Mia Beispiel', 'email' => 'mia@example.invalid']);
        $user->id = 1; // nur für die Adresse im Beispiel (Bestätigungslink)
        $mailable = static fn (object $mail, string $subject): array => [$subject, (string) $mail->render()];
        $message = static fn (MailMessage $mail): array => [$mail->subject, (string) $mail->render()];
        $wish = static fn (WishStatus $status, ?string $answer = null): BookWish => new BookWish(['title' => 'Der Grüffelo', 'status' => $status, 'answer' => $answer, 'contact_name' => 'Mia']);

        return match ($key) {
            'bestaetigen' => $message((new VerifyEmail)->toMail($user)),
            'faellig' => $message((new LibraryReminder('loan_due_soon', 'Der kleine Drache Kokosnuss', '14.10.2026', 0, 'Mia'))->toMail($user)),
            'ueberfaellig' => $message((new LibraryReminder('loan_overdue', 'Das NEINhorn', '02.10.2026', 7, 'Mia'))->toMail($user)),
            'bereit' => $message((new LibraryReminder('reservation_ready', 'Die kleine Spinne Widerlich', '16.10.2026', 0, 'Mia'))->toMail($user)),
            'beleg' => (function (): array {
                $transaction = new LoanTransaction([
                    'number' => 'B-2026-000123',
                    'items' => [
                        ['type' => 'checkout', 'title' => 'Der kleine Drache Kokosnuss', 'due_on' => '2026-10-30'],
                        ['type' => 'checkout', 'title' => 'Weißt du eigentlich, wie lieb ich dich hab?', 'due_on' => '2026-10-30'],
                        ['type' => 'renew', 'title' => 'Das NEINhorn', 'due_on' => '2026-11-06'],
                        ['type' => 'return', 'title' => 'Der Grüffelo', 'returned_on' => '2026-10-09'],
                    ],
                ]);
                $transaction->created_at = now();
                $transaction->setRelation('patron', new Patron(['first_name' => 'Mia']));

                return ['Dein Beleg B-2026-000123 aus der Bibliothek', (string) (new TransactionReceiptMail($transaction))->render()];
            })(),
            'wunsch-angenommen' => $mailable(new WishStatusMail($wish(WishStatus::Accepted)), 'Dein Buchwunsch „Der Grüffelo“: angenommen'),
            'wunsch-bestellt' => $mailable(new WishStatusMail($wish(WishStatus::Ordered)), 'Dein Buchwunsch „Der Grüffelo“: bestellt'),
            'wunsch-da' => $mailable(new WishStatusMail($wish(WishStatus::Fulfilled)), 'Dein Buchwunsch „Der Grüffelo“: erfüllt'),
            'wunsch-abgelehnt' => $mailable(new WishStatusMail($wish(WishStatus::Declined, 'Das Buch ist leider vergriffen. Wir suchen eine Alternative.')), 'Dein Buchwunsch „Der Grüffelo“: abgelehnt'),
            'betrieb' => $mailable(new AlertMail('Die Datensicherung ist fehlgeschlagen', ['Die tägliche Sicherung konnte nicht geschrieben werden: Kein Speicherplatz.', 'Zeit: '.now()->toDateTimeString()]), 'Die Datensicherung ist fehlgeschlagen'),
            default => $message((new ResetPassword('beispiel-token'))->toMail($user)),
        };
    }
}
