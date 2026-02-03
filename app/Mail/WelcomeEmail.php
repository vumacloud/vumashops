<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $userData;

    public function __construct(array $userData)
    {
        $this->userData = $userData;
    }

    public function envelope(): Envelope
    {
        $storeName = $this->userData['store_name'] ?? 'VumaShops';

        return new Envelope(
            subject: "Welcome to {$storeName}!",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'user' => $this->userData,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
