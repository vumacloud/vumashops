<?php

namespace App\Services\Payments;

use App\Models\Payment;

class FlutterwaveGateway extends BaseGateway
{
    protected function getBaseUrl(): string
    {
        return $this->config['base_url'] ?? 'https://api.flutterwave.com/v3';
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
        return 'flutterwave';
    }

    public function getName(): string
    {
        return 'Flutterwave';
    }

    public function getDescription(): string
    {
        return 'Pay with Card, Bank Transfer, or Mobile Money';
    }

    public function getSupportedCountries(): array
    {
        return $this->config['countries'] ?? [
            'NG', 'GH', 'KE', 'UG', 'TZ', 'ZA', 'RW', 'ZM', 'CM', 'CI'
        ];
    }

    public function getSupportedCurrencies(): array
    {
        return $this->config['currencies'] ?? [
            'NGN', 'GHS', 'KES', 'UGX', 'TZS', 'ZAR', 'RWF', 'ZMW', 'XAF', 'XOF', 'USD', 'EUR', 'GBP'
        ];
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

        $payload = [
            'tx_ref' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'redirect_url' => $metadata['callback_url'] ?? route('payment.callback', ['gateway' => 'flutterwave']),
            'customer' => [
                'email' => $customer['email'],
                'phonenumber' => $customer['phone'] ?? null,
                'name' => $customer['name'] ?? null,
            ],
            'customizations' => [
                'title' => $metadata['title'] ?? config('app.name'),
                'logo' => $metadata['logo'] ?? null,
                'description' => $metadata['description'] ?? 'Payment for order',
            ],
            'meta' => $metadata,
        ];

        // Add payment options if specified
        if (isset($metadata['payment_options'])) {
            $payload['payment_options'] = $metadata['payment_options'];
        }

        $response = $this->makeRequest('post', 'payments', $payload);

        $this->logTransaction('initialize', [
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'response' => $response,
        ]);

        if (!$response['success'] || ($response['data']['status'] ?? '') !== 'success') {
            return [
                'success' => false,
                'message' => $response['data']['message'] ?? 'Failed to initialize payment',
                'reference' => $reference,
            ];
        }

        return [
            'success' => true,
            'reference' => $reference,
            'authorization_url' => $response['data']['data']['link'],
        ];
    }

    public function verify(string $reference): array
    {
        // First try to verify by tx_ref
        $response = $this->makeRequest('get', "transactions/verify_by_reference?tx_ref={$reference}");

        $this->logTransaction('verify', [
            'reference' => $reference,
            'response' => $response,
        ]);

        if (!$response['success'] || ($response['data']['status'] ?? '') !== 'success') {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => $response['data']['message'] ?? 'Verification failed',
                'data' => $response['data'],
            ];
        }

        $data = $response['data']['data'] ?? [];
        $status = $data['status'] ?? 'failed';

        return [
            'success' => $status === 'successful',
            'status' => $status,
            'amount' => $data['amount'] ?? 0,
            'currency' => $data['currency'] ?? null,
            'gateway_reference' => $data['id'] ?? null,
            'flw_ref' => $data['flw_ref'] ?? null,
            'payment_type' => $data['payment_type'] ?? null,
            'customer' => [
                'email' => $data['customer']['email'] ?? null,
                'phone' => $data['customer']['phone_number'] ?? null,
                'name' => $data['customer']['name'] ?? null,
            ],
            'card' => $data['card'] ?? null,
            'data' => $data,
        ];
    }

    public function handleWebhook(array $payload): array
    {
        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];

        // Verify webhook using secret hash
        $secretHash = $this->config['secret_hash'] ?? null;
        if ($secretHash) {
            $signature = request()->header('verif-hash');
            if ($signature !== $secretHash) {
                return ['valid' => false, 'message' => 'Invalid signature'];
            }
        }

        $this->logTransaction('webhook', $payload);

        if ($event === 'charge.completed') {
            $status = $data['status'] ?? 'failed';
            return [
                'valid' => true,
                'reference' => $data['tx_ref'] ?? null,
                'status' => $status === 'successful' ? 'success' : 'failed',
                'amount' => $data['amount'] ?? 0,
                'currency' => $data['currency'] ?? null,
                'data' => $data,
            ];
        }

        return [
            'valid' => true,
            'reference' => $data['tx_ref'] ?? null,
            'status' => 'unknown',
            'event' => $event,
            'data' => $data,
        ];
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): array
    {
        $amountToRefund = $amount ?? $payment->amount;

        $payload = [
            'amount' => $amountToRefund,
        ];

        if ($reason) {
            $payload['comments'] = $reason;
        }

        $response = $this->makeRequest('post', "transactions/{$payment->gateway_reference}/refund", $payload);

        $this->logTransaction('refund', [
            'payment_id' => $payment->id,
            'amount' => $amountToRefund,
            'response' => $response,
        ]);

        if (!$response['success'] || ($response['data']['status'] ?? '') !== 'success') {
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
     * Charge via Mobile Money.
     */
    public function chargeMobileMoney(float $amount, string $currency, string $phone, string $network, array $customer, array $metadata = []): array
    {
        $reference = $this->generateReference();

        $payload = [
            'tx_ref' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'phone_number' => $this->formatPhoneNumber($phone, $this->getCountryCode($currency)),
            'network' => $network, // MTN, VODAFONE, TIGO, AIRTEL
            'email' => $customer['email'],
            'fullname' => $customer['name'] ?? 'Customer',
            'redirect_url' => $metadata['callback_url'] ?? route('payment.callback', ['gateway' => 'flutterwave']),
        ];

        $response = $this->makeRequest('post', 'charges?type=mobile_money_ghana', $payload);

        if ($currency === 'UGX') {
            $response = $this->makeRequest('post', 'charges?type=mobile_money_uganda', $payload);
        } elseif ($currency === 'RWF') {
            $response = $this->makeRequest('post', 'charges?type=mobile_money_rwanda', $payload);
        } elseif ($currency === 'ZMW') {
            $response = $this->makeRequest('post', 'charges?type=mobile_money_zambia', $payload);
        } elseif (in_array($currency, ['XAF', 'XOF'])) {
            $response = $this->makeRequest('post', 'charges?type=mobile_money_franco', $payload);
        }

        $this->logTransaction('mobile_money', [
            'reference' => $reference,
            'amount' => $amount,
            'phone' => $phone,
            'response' => $response,
        ]);

        if (!$response['success']) {
            return [
                'success' => false,
                'message' => $response['data']['message'] ?? 'Mobile money charge failed',
                'reference' => $reference,
            ];
        }

        $data = $response['data']['data'] ?? $response['data'] ?? [];

        return [
            'success' => true,
            'reference' => $reference,
            'status' => $data['status'] ?? 'pending',
            'message' => $data['message'] ?? 'Please authorize the payment',
            'data' => $data,
        ];
    }

    /**
     * Get list of banks.
     */
    public function getBanks(string $country = 'NG'): array
    {
        $response = $this->makeRequest('get', "banks/{$country}");

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
        $payload = [
            'account_number' => $accountNumber,
            'account_bank' => $bankCode,
        ];

        $response = $this->makeRequest('post', 'accounts/resolve', $payload);

        return [
            'success' => $response['success'] && ($response['data']['status'] ?? '') === 'success',
            'account_name' => $response['data']['data']['account_name'] ?? null,
            'data' => $response['data']['data'] ?? [],
        ];
    }

    /**
     * Create a virtual account for bank transfer.
     */
    public function createVirtualAccount(string $email, string $bvn, string $name, bool $isPermanent = false): array
    {
        $payload = [
            'email' => $email,
            'bvn' => $bvn,
            'is_permanent' => $isPermanent,
            'tx_ref' => $this->generateReference(),
            'narration' => $name,
        ];

        $response = $this->makeRequest('post', 'virtual-account-numbers', $payload);

        return [
            'success' => $response['success'] && ($response['data']['status'] ?? '') === 'success',
            'account_number' => $response['data']['data']['account_number'] ?? null,
            'bank_name' => $response['data']['data']['bank_name'] ?? null,
            'data' => $response['data']['data'] ?? [],
        ];
    }

    private function getCountryCode(string $currency): string
    {
        return match($currency) {
            'NGN' => '234',
            'GHS' => '233',
            'KES' => '254',
            'UGX' => '256',
            'TZS' => '255',
            'RWF' => '250',
            'ZMW' => '260',
            default => '',
        };
    }
}
