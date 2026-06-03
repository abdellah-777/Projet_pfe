<?php

namespace App\Mail;

use App\Models\ProjectInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProjectInvitation $invitation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to join {$this->invitation->project->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.project-invitation',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl'  => config('app.frontend_url') . '/invitations/' . $this->invitation->token,
            ],
        );
    }
}