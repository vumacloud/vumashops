<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\OrderConfirmation;
use App\Mail\WelcomeEmail;
use App\Mail\PasswordResetEmail;
use App\Mail\PaymentReceived;
use App\Mail\ShippingNotification;
use App\Mail\GenericEmail;

/**
 * Email Service using Laravel's Mail facade with Brevo SMTP.
 * Configure SMTP settings in .env:
 * MAIL_MAILER=smtp
 * MAIL_HOST=smtp-relay.brevo.com
 * MAIL_PORT=587
 * MAIL_USERNAME=your-brevo-smtp-login
 * MAIL_PASSWORD=your-brevo-smtp-password
 * MAIL_ENCRYPTION=tls
 */
class BrevoEmailService
{
    protected string $fromEmail;
    protected string $fromName;

    public function __construct()
    {
        $this->fromEmail = config('mail.from.address', 'noreply@vumacloud.com');
        $this->fromName = config('mail.from.name', 'VumaShops');
    }

    /**
     * Send a generic email.
     */
    public function send(string $to, string $subject, string $htmlContent, array $options = []): array
    {
        try {
            Mail::to($to)->send(new GenericEmail($subject, $htmlContent, $options));

            return [
                'success' => true,
                'message' => 'Email sent successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Email send failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send order confirmation email.
     */
    public function sendOrderConfirmation(string $to, array $orderData): array
    {
        try {
            Mail::to($to)->send(new OrderConfirmation($orderData));

            return [
                'success' => true,
                'message' => 'Order confirmation email sent',
            ];
        } catch (\Exception $e) {
            Log::error('Order confirmation email failed', [
                'to' => $to,
                'order' => $orderData['order_number'] ?? 'N/A',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send welcome email.
     */
    public function sendWelcome(string $to, array $userData): array
    {
        try {
            Mail::to($to)->send(new WelcomeEmail($userData));

            return [
                'success' => true,
                'message' => 'Welcome email sent',
            ];
        } catch (\Exception $e) {
            Log::error('Welcome email failed', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send password reset email.
     */
    public function sendPasswordReset(string $to, string $resetLink, string $name = null): array
    {
        try {
            Mail::to($to)->send(new PasswordResetEmail($resetLink, $name));

            return [
                'success' => true,
                'message' => 'Password reset email sent',
            ];
        } catch (\Exception $e) {
            Log::error('Password reset email failed', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send payment received email.
     */
    public function sendPaymentReceived(string $to, array $paymentData): array
    {
        try {
            Mail::to($to)->send(new PaymentReceived($paymentData));

            return [
                'success' => true,
                'message' => 'Payment received email sent',
            ];
        } catch (\Exception $e) {
            Log::error('Payment received email failed', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send shipping notification email.
     */
    public function sendShippingNotification(string $to, array $shippingData): array
    {
        try {
            Mail::to($to)->send(new ShippingNotification($shippingData));

            return [
                'success' => true,
                'message' => 'Shipping notification email sent',
            ];
        } catch (\Exception $e) {
            Log::error('Shipping notification email failed', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Queue an email for later sending.
     */
    public function queue(string $to, string $subject, string $htmlContent, array $options = []): array
    {
        try {
            Mail::to($to)->queue(new GenericEmail($subject, $htmlContent, $options));

            return [
                'success' => true,
                'message' => 'Email queued successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Email queue failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
