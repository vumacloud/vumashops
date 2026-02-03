<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $emailSubject;
    public string $htmlContent;
    public array $options;

    public function __construct(string $subject, string $htmlContent, array $options = [])
    {
        $this->emailSubject = $subject;
        $this->htmlContent = $htmlContent;
        $this->options = $options;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.generic',
            with: [
                'htmlContent' => $this->htmlContent,
                'options' => $this->options,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
