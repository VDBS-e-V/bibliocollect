<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Actions;

use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Circulation\Enums\WishStatus;
use App\Modules\Circulation\Mail\WishStatusMail;
use App\Modules\Circulation\Models\BookWish;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Mitarbeitende setzen den Stand eines Wunsches (angenommen, bestellt, ist da, abgelehnt) und können kurz antworten. */
final readonly class DecideBookWishAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(BookWish $wish, WishStatus $status, ?string $answer, User $actor): BookWish
    {
        $changed = $wish->status !== $status;

        $wish->forceFill([
            'status' => $status,
            'answer' => $answer !== null && trim($answer) !== '' ? mb_substr(trim($answer), 0, 500) : null,
            'decided_by_user_id' => $actor->getKey(),
            'decided_at' => now(),
        ])->save();

        $this->audit->record('wishes.decided', 'Buchwunsch bearbeitet: '.$status->label().'.', $wish, ['patron_id' => $wish->patron_id, 'status' => $status->value], $actor->getKey());

        if ($changed && $status !== WishStatus::New) {
            $this->notify($wish);
        }

        return $wish;
    }

    /** Eine Mail an die Adresse des Kontos, wenn es eine gibt. Ein Fehler beim Versand ändert nichts am Wunsch. */
    private function notify(BookWish $wish): void
    {
        $patron = $wish->patron;

        if ($patron === null) {
            return;
        }

        $address = is_string($patron->email) && $patron->email !== ''
            ? $patron->email
            : User::query()->where('patron_id', $patron->getKey())->whereNotNull('email_verified_at')->value('email');

        if (! is_string($address) || $address === '') {
            return;
        }

        try {
            Mail::to($address)->send(new WishStatusMail($wish));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
