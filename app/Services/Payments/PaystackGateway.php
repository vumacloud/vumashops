<?php

namespace App\Services\Payments;

use App\Models\Payment;

class PaystackGateway extends BaseGateway
{
    protected function getBaseUrl(): string
    {
        return $this->config['payment_url'] ?? 'https://api.paystack.co';
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->config['secret_key'],
        ];
    }

    public function getIdentifier(): string
    {
        return 'paystack';
    }

    public function getName(): string
    {
        return 'Paystack';
    }

    public function getDescription(): string
    {
        return 'Pay with Card (Visa, Mastercard, Verve)';
    }

    public function getSupportedCountries(): array
    {
        return $this->config['countries'] ?? ['NG', 'GH', 'ZA', 'KE'];
    }

    public function getSupportedCurrencies(): array
    {
        return $this->config['currencies'] ?? ['NGN', 'GHS', 'ZAR', 'KES', 'USD'];
    }

    public function supportsRefunds(): bool
    {
        return true;
    }

    public function supportsRecurring(): bool
    {
        return true;
    }

    public function initialize(float $amount, string $currency, array $customer, array $metadata = []): array
    {
        $reference = $this->generateReference();

        // Convert amount to kobo/pesewas (smallest currency unit)
        $amountInSmallestUnit = (int) round($amount * 100);

        $payload = [
            'email' => $customer['email'],
            'amount' => $amountInSmallestUnit,
            'currency' => $currency,
            'reference' => $reference,
            'callback_url' => $metadata['callback_url'] ?? route('payment.callback', ['gateway' => 'paystack']),
            'metadata' => array_merge($metadata, [
                'customer_name' => $customer['name'] ?? null,
                'customer_phone' => $customer['phone'] ?? null,
            ]),
        ];

        // Add channels if specified
        if (isset($metadata['channels'])) {
            $payload['channels'] = $metadata['channels'];
        }

        $response = $this->makeRequest('post', 'transaction/initialize', $payload);

        $this->logTransaction('initialize', [
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'response' => $response,
        ]);

        if (!$response['success'] || !($response['data']['status'] ?? false)) {
            return [
                'success' => false,
                'message' => $response['data']['message'] ?? 'Failed to initialize payment',
                'reference' => $reference,
            ];
        }

        return [
            'success' => true,
            'reference' => $reference,
            'authorization_url' => $response['data']['data']['authorization_url'],
            'access_code' => $response['data']['data']['access_code'],
        ];
    }

    public function verify(string $reference): array
    {
        $response = $this->makeRequest('get', "transaction/verify/{$reference}");

        $this->logTransaction('verify', [
            'reference' => $reference,
            'response' => $response,
        ]);

        if (!$response['success']) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Verification request failed',
                'data' => $response['data'],
            ];
        }

        $data = $response['data']['data'] ?? [];
        $status = $data['status'] ?? 'failed';

        return [
            'success' => $status === 'success',
            'status' => $status,
            'amount' => ($data['amount'] ?? 0) / 100, // Convert from kobo
            'currency' => $data['currency'] ?? null,
            'gateway_reference' => $data['id'] ?? null,
            'channel' => $data['channel'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
            'customer' => [
                'email' => $data['customer']['email'] ?? null,
                'phone' => $data['customer']['phone'] ?? null,
            ],
            'authorization' => $data['authorization'] ?? null,
            'data' => $data,
        ];
    }

    public function handleWebhook(array $payload): array
    {
        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];

        // Verify webhook signature if configured
        if (isset($this->config['webhook_secret'])) {
            // Signature verification would go here
        }

        $this->logTransaction('webhook', $payload);

        if ($event === 'charge.success') {
            return [
                'valid' => true,
                'reference' => $data['reference'] ?? null,
                'status' => 'success',
                'amount' => ($data['amount'] ?? 0) / 100,
                'currency' => $data['currency'] ?? null,
                'data' => $data,
            ];
        }

        if ($event === 'charge.failed') {
            return [
                'valid' => true,
                'reference' => $data['reference'] ?? null,
                'status' => 'failed',
                'data' => $data,
            ];
        }

        return [
            'valid' => true,
            'reference' => $data['reference'] ?? null,
            'status' => 'unknown',
            'event' => $event,
            'data' => $data,
        ];
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): array
    {
        $amountToRefund = $amount ?? $payment->amount;
        $amountInSmallestUnit = (int) round($amountToRefund * 100);

        $payload = [
            'transaction' => $payment->gateway_reference,
            'amount' => $amountInSmallestUnit,
        ];

        if ($reason) {
            $payload['reason'] = $reason;
        }

        $response = $this->makeRequest('post', 'refund', $payload);

        $this->logTransaction('refund', [
            'payment_id' => $payment->id,
            'amount' => $amountToRefund,
            'response' => $response,
        ]);

        if (!$response['success'] || !($response['data']['status'] ?? false)) {
            return [
                'success' => false,
                'message' => $response['data']['message'] ?? 'Refund failed',
            ];
        }

        return [
            'success' => true,
            'reference' => $response['data']['data']['id'] ?? null,
            'amount' => $amountToRefund,
            'data' => $response['data']['data'],
        ];
    }

    /**
     * Create a subscription plan.
     */
    public function createPlan(string $name, float $amount, string $interval, string $currency = 'NGN'): array
    {
        $payload = [
            'name' => $name,
            'amount' => (int) round($amount * 100),
            'interval' => $interval, // 'hourly', 'daily', 'weekly', 'monthly', 'annually'
            'currency' => $currency,
        ];

        $response = $this->makeRequest('post', 'plan', $payload);

        return [
            'success' => $response['success'] && ($response['data']['status'] ?? false),
            'plan_code' => $response['data']['data']['plan_code'] ?? null,
            'data' => $response['data']['data'] ?? [],
        ];
    }

    /**
     * Create a subscription for a customer.
     */
    public function createSubscription(string $customerEmail, string $planCode, string $authorizationCode): array
    {
        $payload = [
            'customer' => $customerEmail,
            'plan' => $planCode,
            'authorization' => $authorizationCode,
        ];

        $response = $this->makeRequest('post', 'subscription', $payload);

        return [
            'success' => $response['success'] && ($response['data']['status'] ?? false),
            'subscription_code' => $response['data']['data']['subscription_code'] ?? null,
            'data' => $response['data']['data'] ?? [],
        ];
    }

    /**
     * Charge an authorization (for recurring payments).
     */
    public function chargeAuthorization(string $authorizationCode, string $email, float $amount, string $currency = 'NGN'): array
    {
        $reference = $this->generateReference();

        $payload = [
            'authorization_code' => $authorizationCode,
            'email' => $email,
            'amount' => (int) round($amount * 100),
            'currency' => $currency,
            'reference' => $reference,
        ];

        $response = $this->makeRequest('post', 'transaction/charge_authorization', $payload);

        return [
            'success' => $response['success'] && ($response['data']['data']['status'] ?? '') === 'success',
            'reference' => $reference,
            'data' => $response['data']['data'] ?? [],
        ];
    }

    /**
     * Get list of banks for transfer.
     */
    public function getBanks(string $country = 'nigeria'): array
    {
        $response = $this->makeRequest('get', "bank?country={$country}");

        return [
            'success' => $response['success'],
            'banks' => $response['data']['data'] ?? [],
        ];
    }

    /**
     * Verify bank account.
     */
    public function resolveAccount(string $accountNumber, string $bankCode): array
    {
        $response = $this->makeRequest('get', "bank/resolve?account_number={$accountNumber}&bank_code={$bankCode}");

        return [
            'success' => $response['success'] && ($response['data']['status'] ?? false),
            'account_name' => $response['data']['data']['account_name'] ?? null,
            'data' => $response['data']['data'] ?? [],
        ];
    }
}
