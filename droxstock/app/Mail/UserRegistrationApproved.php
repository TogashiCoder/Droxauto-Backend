<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserRegistrationApproved extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $adminNotes;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $adminNotes = '')
    {
        $this->user = $user;
        $this->adminNotes = $adminNotes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account Approved - Welcome to Our System!',
            to: [$this->user->email],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.user-registration-approved',
            with: [
                'user' => $this->user,
                'adminNotes' => $this->adminNotes,
                'loginUrl' => config('app.frontend_url', config('app.url')) . '/login',
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
