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

    public $digestSections;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, array $quotes, array $digestSections = [])
    {
        $this->user = $user;
        $this->quotes = $quotes;
        $this->digestSections = $digestSections;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Daily Book Quotes - '.now()->format('F j, Y'),
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
                'date' => now()->format('F j, Y'),
                'todaysSnippet' => $this->digestSections['todaysSnippet'] ?? null,
                'crossBookConnection' => $this->digestSections['crossBookConnection'] ?? null,
                'quoteToPonder' => $this->digestSections['quoteToPonder'] ?? null,
                'todaysReflection' => $this->digestSections['todaysReflection'] ?? null,
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
