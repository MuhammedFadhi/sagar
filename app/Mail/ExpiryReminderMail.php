<?php

namespace App\Mail;

use App\Models\EmployeeDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $doc;
    public $daysLeft;

    /**
     * Create a new message instance.
     */
    public function __construct(EmployeeDocument $doc, int $daysLeft)
    {
        $this->doc = $doc;
        $this->daysLeft = $daysLeft;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Document Expiry Reminder: ' . $this->doc->type,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.expiry_reminder',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
