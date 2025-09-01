<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserRegistrationRejected extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $rejectionReason;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $rejectionReason = '')
    {
        $this->user = $user;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account Registration Rejected',
            to: [$this->user->email],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.user-registration-rejected',
            with: [
                'user' => $this->user,
                'rejectionReason' => $this->rejectionReason,
                'contactEmail' => config('mail.admin_email', 'admin@example.com'),
            ],
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
}
