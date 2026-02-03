<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $paymentData;

    public function __construct(array $paymentData)
    {
        $this->paymentData = $paymentData;
    }

    public function envelope(): Envelope
    {
        $orderNumber = $this->paymentData['order_number'] ?? 'N/A';

        return new Envelope(
            subject: "Payment Received - Order #{$orderNumber}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-received',
            with: [
                'payment' => $this->paymentData,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
