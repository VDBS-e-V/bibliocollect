<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Actions;

use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Circulation\Mail\TransactionReceiptMail;
use App\Modules\Circulation\Models\LoanTransaction;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Schickt den Beleg eines Vorgangs per E-Mail. Ein Fehler beim Versand darf den Betrieb am Tresen nie stören. */
final readonly class SendTransactionReceiptAction
{
    public function __construct(private AuditRecorder $audit) {}

    /** Adresse am Ausleihkonto, sonst die Adresse des bestätigten Onlinekontos; null, wenn es keine gibt. */
    public function recipient(LoanTransaction $transaction): ?string
    {
        $patron = $transaction->patron;

        if ($patron === null) {
            return null;
        }

        if (is_string($patron->email) && filter_var($patron->email, FILTER_VALIDATE_EMAIL) !== false) {
            return $patron->email;
        }

        $user = User::query()->where('patron_id', $patron->getKey())->whereNotNull('email_verified_at')->first();

        return $user !== null && filter_var($user->email, FILTER_VALIDATE_EMAIL) !== false ? $user->email : null;
    }

    public function execute(LoanTransaction $transaction, string $address): bool
    {
        try {
            Mail::to($address)->send(new TransactionReceiptMail($transaction));
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        $transaction->forceFill(['emailed_to' => $address, 'emailed_at' => now()])->save();
        $this->audit->record('circulation.transaction.emailed', "Beleg {$transaction->number} automatisch per E-Mail verschickt.", $transaction);

        return true;
    }
}
