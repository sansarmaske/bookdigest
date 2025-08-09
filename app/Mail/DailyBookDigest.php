<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyBookDigest extends Mailable // implements ShouldQueue - disabled for testing
{
    use Queueable, SerializesModels;

    public $user;
    public $quotes;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, array $quotes)
    {
        $this->user = $user;
        $this->quotes = $quotes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Daily Book Quotes - ' . now()->format('F j, Y'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-digest',
            with: [
                'user' => $this->user,
                'quotes' => $this->quotes,
                'date' => now()->format('F j, Y')
            ]
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
