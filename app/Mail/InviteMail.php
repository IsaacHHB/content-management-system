<?php

namespace App\Mail;

use App\Models\Invite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class InviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invite $invite, public string $plainToken) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Native Dads Network CMS invitation');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.invite', with: [
            'url' => URL::temporarySignedRoute(
                'invite.show',
                $this->invite->expires_at,
                ['token' => $this->plainToken],
            ),
        ]);
    }
}
