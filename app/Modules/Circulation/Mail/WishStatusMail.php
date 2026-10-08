<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Mail;

use App\Modules\Circulation\Models\BookWish;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Nachricht über den Stand eines Buchwunsches. */
final class WishStatusMail extends Mailable
{
    public function __construct(public readonly BookWish $wish) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Dein Buchwunsch „'.$this->wish->title.'“: '.$this->wish->status->label());
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.wish-status');
    }
}
