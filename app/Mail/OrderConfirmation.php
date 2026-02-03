<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $orderData;

    public function __construct(array $orderData)
    {
        $this->orderData = $orderData;
    }

    public function envelope(): Envelope
    {
        $orderNumber = $this->orderData['order_number'] ?? 'N/A';
        $storeName = $this->orderData['store_name'] ?? 'VumaShops';

        return new Envelope(
            subject: "Order Confirmed #{$orderNumber} - {$storeName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
            with: [
                'order' => $this->orderData,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
