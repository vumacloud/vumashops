<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AfricasTalkingSmsService
{
    protected string $username;
    protected string $apiKey;
    protected ?string $from;
    protected bool $sandbox;
    protected string $baseUrl;

    public function __construct()
    {
        $config = config('notifications.sms.africastalking');
        $this->username = $config['username'] ?? 'sandbox';
        $this->apiKey = $config['api_key'] ?? '';
        $this->from = $config['from'] ?? null;
        $this->sandbox = $config['sandbox'] ?? true;

        $this->baseUrl = $this->sandbox
            ? 'https://api.sandbox.africastalking.com/version1'
            : 'https://api.africastalking.com/version1';
    }

    /**
     * Send SMS to single or multiple recipients.
     */
    public function send(string|array $to, string $message, array $options = []): array
    {
        $recipients = is_array($to) ? implode(',', $to) : $to;

        $payload = [
            'username' => $this->username,
            'to' => $recipients,
            'message' => $message,
        ];

        if ($this->from || !empty($options['from'])) {
            $payload['from'] = $options['from'] ?? $this->from;
        }

        if (!empty($options['enqueue'])) {
            $payload['enqueue'] = '1';
        }

        if (!empty($options['keyword'])) {
            $payload['keyword'] = $options['keyword'];
        }

        if (!empty($options['linkId'])) {
            $payload['linkId'] = $options['linkId'];
        }

        if (!empty($options['retryDurationInHours'])) {
            $payload['retryDurationInHours'] = $options['retryDurationInHours'];
        }

        $response = $this->makeRequest('POST', '/messaging', $payload);

        $this->logSms($recipients, $message, $response);

        return $response;
    }

    /**
     * Send bulk SMS with same message.
     */
    public function sendBulk(array $recipients, string $message, array $options = []): array
    {
        return $this->send($recipients, $message, array_merge($options, ['enqueue' => true]));
    }

    /**
     * Send premium SMS.
     */
    public function sendPremium(string $to, string $message, string $keyword, string $linkId, array $options = []): array
    {
        $payload = [
            'username' => $this->username,
            'to' => $to,
            'message' => $message,
            'keyword' => $keyword,
            'linkId' => $linkId,
        ];

        if ($this->from) {
            $payload['from'] = $this->from;
        }

        if (!empty($options['retryDurationInHours'])) {
            $payload['retryDurationInHours'] = $options['retryDurationInHours'];
        }

        return $this->makeRequest('POST', '/messaging', $payload);
    }

    /**
     * Fetch SMS messages.
     */
    public function fetchMessages(int $lastReceivedId = 0): array
    {
        $params = [
            'username' => $this->username,
            'lastReceivedId' => $lastReceivedId,
        ];

        return $this->makeRequest('GET', '/messaging', $params);
    }

    /**
     * Create subscription.
     */
    public function createSubscription(string $phoneNumber, string $shortCode, string $keyword): array
    {
        $payload = [
            'username' => $this->username,
            'phoneNumber' => $phoneNumber,
            'shortCode' => $shortCode,
            'keyword' => $keyword,
        ];

        return $this->makeRequest('POST', '/subscription/create', $payload);
    }

    /**
     * Delete subscription.
     */
    public function deleteSubscription(string $phoneNumber, string $shortCode, string $keyword): array
    {
        $payload = [
            'username' => $this->username,
            'phoneNumber' => $phoneNumber,
            'shortCode' => $shortCode,
            'keyword' => $keyword,
        ];

        return $this->makeRequest('POST', '/subscription/delete', $payload);
    }

    /**
     * Fetch subscriptions.
     */
    public function fetchSubscriptions(string $shortCode, string $keyword, int $lastReceivedId = 0): array
    {
        $params = [
            'username' => $this->username,
            'shortCode' => $shortCode,
            'keyword' => $keyword,
            'lastReceivedId' => $lastReceivedId,
        ];

        return $this->makeRequest('GET', '/subscription', $params);
    }

    /**
     * Get account balance (using application endpoint).
     */
    public function getBalance(): array
    {
        $url = $this->sandbox
            ? 'https://api.sandbox.africastalking.com/version1/user'
            : 'https://api.africastalking.com/version1/user';

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'apiKey' => $this->apiKey,
            ])->get($url, ['username' => $this->username]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Make API request.
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;

            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'apiKey' => $this->apiKey,
            ];

            if (strtoupper($method) === 'GET') {
                $response = Http::withHeaders($headers)->get($url, $data);
            } else {
                $response = Http::withHeaders($headers)->asForm()->post($url, $data);
            }

            $responseData = $response->json();

            if (!$response->successful()) {
                Log::error("Africa's Talking API Error", [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'response' => $responseData,
                ]);
            }

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error("Africa's Talking API Exception", [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 0,
                'data' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Log SMS for tracking.
     */
    protected function logSms(string $recipients, string $message, array $response): void
    {
        Log::channel('sms')->info('SMS Sent', [
            'recipients' => $recipients,
            'message_length' => strlen($message),
            'response' => $response,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Format phone number for Africa's Talking.
     */
    public function formatPhoneNumber(string $phone, string $countryCode = '254'): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = $countryCode . substr($phone, 1);
        } elseif (!str_starts_with($phone, '+') && strlen($phone) <= 10) {
            $phone = $countryCode . $phone;
        }

        return '+' . $phone;
    }

    /**
     * Pre-built SMS templates.
     */
    public function sendOrderConfirmation(string $phone, array $orderData): array
    {
        $template = config('notifications.sms.templates.order_confirmation');
        $message = $this->parseTemplate($template, [
            'customer_name' => $orderData['customer_name'],
            'order_number' => $orderData['order_number'],
            'currency' => $orderData['currency'],
            'total' => $orderData['total'],
            'tracking_url' => $orderData['tracking_url'] ?? '',
        ]);

        return $this->send($phone, $message);
    }

    public function sendPaymentReceived(string $phone, array $paymentData): array
    {
        $template = config('notifications.sms.templates.payment_received');
        $message = $this->parseTemplate($template, [
            'customer_name' => $paymentData['customer_name'],
            'currency' => $paymentData['currency'],
            'amount' => $paymentData['amount'],
            'order_number' => $paymentData['order_number'],
        ]);

        return $this->send($phone, $message);
    }

    public function sendOtp(string $phone, string $otp, int $validityMinutes = 10): array
    {
        $template = config('notifications.sms.templates.otp_verification');
        $message = $this->parseTemplate($template, [
            'otp' => $otp,
            'validity' => $validityMinutes,
        ]);

        return $this->send($phone, $message);
    }

    public function sendShippingNotification(string $phone, array $shippingData): array
    {
        $template = config('notifications.sms.templates.order_shipped');
        $message = $this->parseTemplate($template, [
            'customer_name' => $shippingData['customer_name'],
            'order_number' => $shippingData['order_number'],
            'delivery_date' => $shippingData['estimated_delivery'] ?? 'soon',
            'tracking_url' => $shippingData['tracking_url'] ?? '',
        ]);

        return $this->send($phone, $message);
    }

    public function sendLowStockAlert(string $phone, array $productData): array
    {
        $template = config('notifications.sms.templates.low_stock_alert');
        $message = $this->parseTemplate($template, [
            'product_name' => $productData['name'],
            'quantity' => $productData['quantity'],
        ]);

        return $this->send($phone, $message);
    }

    /**
     * Parse template with variables.
     */
    protected function parseTemplate(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value ?? '', $template);
        }

        return $template;
    }

    /**
     * Airtime methods (bonus feature).
     */
    public function sendAirtime(array $recipients): array
    {
        $url = $this->sandbox
            ? 'https://api.sandbox.africastalking.com/version1/airtime/send'
            : 'https://api.africastalking.com/version1/airtime/send';

        $payload = [
            'username' => $this->username,
            'recipients' => json_encode($recipients),
        ];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'apiKey' => $this->apiKey,
            ])->asForm()->post($url, $payload);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
