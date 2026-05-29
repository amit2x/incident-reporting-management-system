<?php

// app/Mail/ContactFormMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $emailData;

    public ?string $attachmentPath;

    public ?string $attachmentOriginalName;

    public ?string $attachmentMime;

    /**
     * Create a new message instance.
     */
    public function __construct(
        array $emailData,
        ?string $attachmentPath = null,
        ?string $attachmentOriginalName = null,
        ?string $attachmentMime = null
    ) {
        $this->emailData = $emailData;
        $this->attachmentPath = $attachmentPath;
        $this->attachmentOriginalName = $attachmentOriginalName;
        $this->attachmentMime = $attachmentMime;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // FIXED: Properly create Address objects for replyTo
        $replyToAddress = new Address(
            $this->emailData['email'],
            $this->emailData['name']
        );

        return new Envelope(
            subject: '[IRMS Contact] '.$this->emailData['subject'],
            replyTo: [$replyToAddress],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
            with: [
                'name' => $this->emailData['name'],
                'email' => $this->emailData['email'],
                'subject' => $this->emailData['subject'],
                'category' => $this->emailData['category'],
                'userMessage' => $this->emailData['userMessage'],
                'isAuthenticated' => $this->emailData['isAuthenticated'],
                'user' => $this->emailData['user'],
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        if (! $this->attachmentPath || ! $this->attachmentOriginalName) {
            return [];
        }

        return [
            Attachment::fromPath(storage_path('app/'.$this->attachmentPath))
                ->as($this->attachmentOriginalName)
                ->withMime($this->attachmentMime ?? 'application/octet-stream'),
        ];
    }
}
