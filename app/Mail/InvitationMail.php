<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invitation;
    public $location;
    public $inviter;

    /**
     * Create a new message instance.
     */
    public function __construct($invitation, $location, $inviter)
    {
        $this->invitation = $invitation;
        $this->location = $location;
        $this->inviter = $inviter;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You are invited to join ' . $this->location->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invitation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('You are invited to join ' . $this->location->name)
            ->markdown('emails.invitation')
            ->with([
                'invitation' => $this->invitation,
                'location' => $this->location,
                'inviter' => $this->inviter,
            ]);
    }
}
