<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoEmailService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.brevo.com/v3';
    protected string $fromEmail;
    protected string $fromName;
    protected ?string $replyTo;

    public function __construct()
    {
        $config = config('notifications.email.brevo');
        $this->apiKey = $config['api_key'] ?? '';
        $this->fromEmail = $config['from_email'] ?? 'noreply@vumashops.com';
        $this->fromName = $config['from_name'] ?? 'VumaShops';
        $this->replyTo = $config['reply_to'] ?? null;
    }

    /**
     * Send a transactional email.
     */
    public function send(string $to, string $subject, string $htmlContent, array $options = []): array
    {
        $payload = [
            'sender' => [
                'name' => $options['from_name'] ?? $this->fromName,
                'email' => $options['from_email'] ?? $this->fromEmail,
            ],
            'to' => [
                ['email' => $to, 'name' => $options['to_name'] ?? null],
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ];

        if ($this->replyTo) {
            $payload['replyTo'] = ['email' => $this->replyTo];
        }

        if (!empty($options['text_content'])) {
            $payload['textContent'] = $options['text_content'];
        }

        if (!empty($options['cc'])) {
            $payload['cc'] = array_map(fn($email) => ['email' => $email], (array) $options['cc']);
        }

        if (!empty($options['bcc'])) {
            $payload['bcc'] = array_map(fn($email) => ['email' => $email], (array) $options['bcc']);
        }

        if (!empty($options['attachments'])) {
            $payload['attachment'] = $options['attachments'];
        }

        if (!empty($options['tags'])) {
            $payload['tags'] = (array) $options['tags'];
        }

        if (!empty($options['params'])) {
            $payload['params'] = $options['params'];
        }

        return $this->makeRequest('POST', '/smtp/email', $payload);
    }

    /**
     * Send email using a template.
     */
    public function sendTemplate(int $templateId, string $to, array $params = [], array $options = []): array
    {
        $payload = [
            'templateId' => $templateId,
            'to' => [
                ['email' => $to, 'name' => $options['to_name'] ?? null],
            ],
            'params' => $params,
        ];

        if (!empty($options['cc'])) {
            $payload['cc'] = array_map(fn($email) => ['email' => $email], (array) $options['cc']);
        }

        if (!empty($options['bcc'])) {
            $payload['bcc'] = array_map(fn($email) => ['email' => $email], (array) $options['bcc']);
        }

        if (!empty($options['attachments'])) {
            $payload['attachment'] = $options['attachments'];
        }

        if (!empty($options['tags'])) {
            $payload['tags'] = (array) $options['tags'];
        }

        return $this->makeRequest('POST', '/smtp/email', $payload);
    }

    /**
     * Send bulk emails.
     */
    public function sendBulk(array $recipients, string $subject, string $htmlContent, array $options = []): array
    {
        $payload = [
            'sender' => [
                'name' => $options['from_name'] ?? $this->fromName,
                'email' => $options['from_email'] ?? $this->fromEmail,
            ],
            'to' => array_map(function ($recipient) {
                if (is_string($recipient)) {
                    return ['email' => $recipient];
                }
                return ['email' => $recipient['email'], 'name' => $recipient['name'] ?? null];
            }, $recipients),
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ];

        if (!empty($options['schedule_at'])) {
            $payload['scheduledAt'] = $options['schedule_at'];
        }

        return $this->makeRequest('POST', '/smtp/email', $payload);
    }

    /**
     * Create or update a contact.
     */
    public function createContact(string $email, array $attributes = [], array $listIds = []): array
    {
        $payload = [
            'email' => $email,
            'updateEnabled' => true,
        ];

        if (!empty($attributes)) {
            $payload['attributes'] = $attributes;
        }

        if (!empty($listIds)) {
            $payload['listIds'] = $listIds;
        }

        return $this->makeRequest('POST', '/contacts', $payload);
    }

    /**
     * Delete a contact.
     */
    public function deleteContact(string $email): array
    {
        return $this->makeRequest('DELETE', '/contacts/' . urlencode($email));
    }

    /**
     * Get contact information.
     */
    public function getContact(string $email): array
    {
        return $this->makeRequest('GET', '/contacts/' . urlencode($email));
    }

    /**
     * Add contact to a list.
     */
    public function addToList(int $listId, array $emails): array
    {
        return $this->makeRequest('POST', '/contacts/lists/' . $listId . '/contacts/add', [
            'emails' => $emails,
        ]);
    }

    /**
     * Remove contact from a list.
     */
    public function removeFromList(int $listId, array $emails): array
    {
        return $this->makeRequest('POST', '/contacts/lists/' . $listId . '/contacts/remove', [
            'emails' => $emails,
        ]);
    }

    /**
     * Create an email campaign.
     */
    public function createCampaign(string $name, string $subject, string $htmlContent, int $listId, array $options = []): array
    {
        $payload = [
            'name' => $name,
            'subject' => $subject,
            'sender' => [
                'name' => $options['from_name'] ?? $this->fromName,
                'email' => $options['from_email'] ?? $this->fromEmail,
            ],
            'type' => 'classic',
            'htmlContent' => $htmlContent,
            'recipients' => [
                'listIds' => [$listId],
            ],
        ];

        if (!empty($options['schedule_at'])) {
            $payload['scheduledAt'] = $options['schedule_at'];
        }

        return $this->makeRequest('POST', '/emailCampaigns', $payload);
    }

    /**
     * Send a campaign.
     */
    public function sendCampaign(int $campaignId): array
    {
        return $this->makeRequest('POST', '/emailCampaigns/' . $campaignId . '/sendNow');
    }

    /**
     * Get email statistics.
     */
    public function getEmailStats(string $startDate, string $endDate): array
    {
        return $this->makeRequest('GET', '/smtp/statistics/events', [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Get transactional email reports.
     */
    public function getTransactionalReports(string $email = null, string $event = null, int $days = 7): array
    {
        $params = [
            'days' => $days,
        ];

        if ($email) {
            $params['email'] = $email;
        }

        if ($event) {
            $params['event'] = $event;
        }

        return $this->makeRequest('GET', '/smtp/statistics/reports', $params);
    }

    /**
     * Create a contact list.
     */
    public function createList(string $name, int $folderId = null): array
    {
        $payload = ['name' => $name];

        if ($folderId) {
            $payload['folderId'] = $folderId;
        }

        return $this->makeRequest('POST', '/contacts/lists', $payload);
    }

    /**
     * Get all lists.
     */
    public function getLists(int $limit = 50, int $offset = 0): array
    {
        return $this->makeRequest('GET', '/contacts/lists', [
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * Make API request.
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'api-key' => $this->apiKey,
            ])->{strtolower($method)}($url, $data);

            $responseData = $response->json();

            if (!$response->successful()) {
                Log::error('Brevo API Error', [
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
            Log::error('Brevo API Exception', [
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
     * Pre-built email templates.
     */
    public function sendOrderConfirmation(string $to, array $orderData): array
    {
        $templateId = config('notifications.email.templates.order_confirmation', 2);

        return $this->sendTemplate($templateId, $to, [
            'ORDER_NUMBER' => $orderData['order_number'],
            'CUSTOMER_NAME' => $orderData['customer_name'],
            'ORDER_TOTAL' => $orderData['total'],
            'CURRENCY' => $orderData['currency'],
            'ORDER_DATE' => $orderData['date'],
            'ORDER_ITEMS' => $orderData['items'] ?? [],
            'STORE_NAME' => $orderData['store_name'],
            'STORE_URL' => $orderData['store_url'],
        ]);
    }

    public function sendWelcome(string $to, array $userData): array
    {
        $templateId = config('notifications.email.templates.welcome', 1);

        return $this->sendTemplate($templateId, $to, [
            'CUSTOMER_NAME' => $userData['name'],
            'STORE_NAME' => $userData['store_name'],
            'STORE_URL' => $userData['store_url'],
        ]);
    }

    public function sendPasswordReset(string $to, string $resetLink, string $name = null): array
    {
        $templateId = config('notifications.email.templates.password_reset', 6);

        return $this->sendTemplate($templateId, $to, [
            'CUSTOMER_NAME' => $name,
            'RESET_LINK' => $resetLink,
        ]);
    }

    public function sendPaymentReceived(string $to, array $paymentData): array
    {
        $templateId = config('notifications.email.templates.payment_received', 8);

        return $this->sendTemplate($templateId, $to, [
            'CUSTOMER_NAME' => $paymentData['customer_name'],
            'AMOUNT' => $paymentData['amount'],
            'CURRENCY' => $paymentData['currency'],
            'ORDER_NUMBER' => $paymentData['order_number'],
            'PAYMENT_METHOD' => $paymentData['payment_method'],
            'PAYMENT_DATE' => $paymentData['date'],
        ]);
    }

    public function sendShippingNotification(string $to, array $shippingData): array
    {
        $templateId = config('notifications.email.templates.order_shipped', 3);

        return $this->sendTemplate($templateId, $to, [
            'CUSTOMER_NAME' => $shippingData['customer_name'],
            'ORDER_NUMBER' => $shippingData['order_number'],
            'TRACKING_NUMBER' => $shippingData['tracking_number'] ?? null,
            'CARRIER' => $shippingData['carrier'] ?? null,
            'TRACKING_URL' => $shippingData['tracking_url'] ?? null,
            'DELIVERY_DATE' => $shippingData['estimated_delivery'] ?? null,
        ]);
    }
}
