<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShippingNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $shippingData;

    public function __construct(array $shippingData)
    {
        $this->shippingData = $shippingData;
    }

    public function envelope(): Envelope
    {
        $orderNumber = $this->shippingData['order_number'] ?? 'N/A';

        return new Envelope(
            subject: "Your Order #{$orderNumber} Has Been Shipped!",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shipping-notification',
            with: [
                'shipping' => $this->shippingData,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
