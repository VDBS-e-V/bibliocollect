<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Mail;

use App\Modules\Circulation\Models\LoanTransaction;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Beleg über einen Vorgang am Tresen: was ausgeliehen, verlängert und zurückgegeben wurde. */
final class TransactionReceiptMail extends Mailable
{
    public function __construct(public readonly LoanTransaction $transaction) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Dein Beleg '.$this->transaction->number.' aus der Bibliothek');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.transaction-receipt');
    }
}
