<?php

declare(strict_types=1);

namespace App\Foundation\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Betriebsmeldung an die Administration. Enthält nie Anfragedaten, Cookies oder Passwörter. */
final class AlertMail extends Mailable
{
    /** @param list<string> $lines */
    public function __construct(public readonly string $alertSubject, public readonly array $lines) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '['.config('app.name', 'BiblioCollect').'] '.$this->alertSubject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.alert');
    }
}
