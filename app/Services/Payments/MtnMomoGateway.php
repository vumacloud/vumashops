<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MtnMomoGateway extends BaseGateway
{
    protected function getBaseUrl(): string
    {
        $env = $this->config['environment'] ?? 'sandbox';
        return $env === 'production'
            ? ($this->config['production_url'] ?? 'https://momodeveloper.mtn.com')
            : ($this->config['sandbox_url'] ?? 'https://sandbox.momodeveloper.mtn.com');
    }

    public function getIdentifier(): string
    {
        return 'mtn_momo';
    }

    public function getName(): string
    {
        return 'MTN Mobile Money';
    }

    public function getDescription(): string
    {
        return 'Pay with MTN Mobile Money';
    }

    public function getSupportedCountries(): array
    {
        return $this->config['countries'] ?? ['UG', 'GH', 'CI', 'CM', 'RW', 'ZM', 'BJ', 'CG', 'SZ'];
    }

    public function getSupportedCurrencies(): array
    {
        return $this->config['currencies'] ?? ['UGX', 'GHS', 'XOF', 'XAF', 'RWF', 'ZMW', 'EUR'];
    }

    /**
     * Get access token for Collection API.
     */
    protected function getCollectionToken(): string
    {
        $cacheKey = 'mtn_momo_collection_token';

        return Cache::remember($cacheKey, 3500, function () {
            $apiUser = $this->config['collection']['api_user'];
            $apiKey = $this->config['collection']['api_key'];
            $subscriptionKey = $this->config['collection']['subscription_key'];

            $credentials = base64_encode($apiUser . ':' . $apiKey);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Basic ' . $credentials,
                'Ocp-Apim-Subscription-Key' => $subscriptionKey,
            ])->post($this->baseUrl . '/collection/token/');

            if (!$response->successful()) {
                throw new \Exception('Failed to get MTN MoMo access token');
            }

            return $response->json('access_token');
        });
    }

    /**
     * Get access token for Disbursement API.
     */
    protected function getDisbursementToken(): string
    {
        $cacheKey = 'mtn_momo_disbursement_token';

        return Cache::remember($cacheKey, 3500, function () {
            $apiUser = $this->config['disbursement']['api_user'];
            $apiKey = $this->config['disbursement']['api_key'];
            $subscriptionKey = $this->config['disbursement']['subscription_key'];

            $credentials = base64_encode($apiUser . ':' . $apiKey);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Basic ' . $credentials,
                'Ocp-Apim-Subscription-Key' => $subscriptionKey,
            ])->post($this->baseUrl . '/disbursement/token/');

            if (!$response->successful()) {
                throw new \Exception('Failed to get MTN MoMo disbursement token');
            }

            return $response->json('access_token');
        });
    }

    protected function getCollectionHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getCollectionToken(),
            'Ocp-Apim-Subscription-Key' => $this->config['collection']['subscription_key'],
            'X-Target-Environment' => $this->config['environment'] ?? 'sandbox',
        ];
    }

    protected function getDisbursementHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getDisbursementToken(),
            'Ocp-Apim-Subscription-Key' => $this->config['disbursement']['subscription_key'],
            'X-Target-Environment' => $this->config['environment'] ?? 'sandbox',
        ];
    }

    /**
     * Request to Pay (Collection).
     */
    public function initialize(float $amount, string $currency, array $customer, array $metadata = []): array
    {
        $reference = (string) Str::uuid();
        $externalId = $this->generateReference();

        $phone = $this->formatMomoPhone($customer['phone'] ?? '');

        $payload = [
            'amount' => (string) $amount,
            'currency' => $currency ?: ($this->config['currency'] ?? 'EUR'),
            'externalId' => $externalId,
            'payer' => [
                'partyIdType' => 'MSISDN',
                'partyId' => $phone,
            ],
            'payerMessage' => $metadata['message'] ?? 'Payment request',
            'payeeNote' => $metadata['note'] ?? 'Payment from ' . config('app.name'),
        ];

        if (!empty($this->config['callback_url'])) {
            $payload['callbackUrl'] = $this->config['callback_url'];
        }

        $headers = array_merge($this->getCollectionHeaders(), [
            'X-Reference-Id' => $reference,
        ]);

        $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
            ->post($this->baseUrl . '/collection/v1_0/requesttopay', $payload);

        $this->logTransaction('request_to_pay', [
            'reference' => $reference,
            'external_id' => $externalId,
            'phone' => $phone,
            'amount' => $amount,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        if ($response->status() !== 202) {
            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Request to pay failed',
                'reference' => $externalId,
            ];
        }

        return [
            'success' => true,
            'reference' => $externalId,
            'momo_reference' => $reference,
            'message' => 'Payment request sent. Please approve on your phone.',
        ];
    }

    /**
     * Check Request to Pay status.
     */
    public function verify(string $reference): array
    {
        $response = \Illuminate\Support\Facades\Http::withHeaders($this->getCollectionHeaders())
            ->get($this->baseUrl . '/collection/v1_0/requesttopay/' . $reference);

        $this->logTransaction('verify', [
            'reference' => $reference,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Verification failed',
                'data' => $response->json(),
            ];
        }

        $data = $response->json();
        $status = $data['status'] ?? 'FAILED';

        return [
            'success' => $status === 'SUCCESSFUL',
            'status' => strtolower($status),
            'amount' => $data['amount'] ?? 0,
            'currency' => $data['currency'] ?? null,
            'external_id' => $data['externalId'] ?? null,
            'payer' => $data['payer'] ?? null,
            'reason' => $data['reason'] ?? null,
            'data' => $data,
        ];
    }

    /**
     * Handle webhook callback.
     */
    public function handleWebhook(array $payload): array
    {
        $this->logTransaction('callback', $payload);

        $status = $payload['status'] ?? 'FAILED';
        $externalId = $payload['externalId'] ?? null;
        $financialTransactionId = $payload['financialTransactionId'] ?? null;

        return [
            'valid' => true,
            'reference' => $externalId,
            'status' => $status === 'SUCCESSFUL' ? 'success' : 'failed',
            'amount' => $payload['amount'] ?? 0,
            'currency' => $payload['currency'] ?? null,
            'transaction_id' => $financialTransactionId,
            'data' => $payload,
        ];
    }

    /**
     * Transfer (Disbursement).
     */
    public function sendMoney(float $amount, string $phone, string $currency = null, string $note = 'Transfer'): array
    {
        $reference = (string) Str::uuid();
        $externalId = $this->generateReference();

        $phone = $this->formatMomoPhone($phone);

        $payload = [
            'amount' => (string) $amount,
            'currency' => $currency ?: ($this->config['currency'] ?? 'EUR'),
            'externalId' => $externalId,
            'payee' => [
                'partyIdType' => 'MSISDN',
                'partyId' => $phone,
            ],
            'payerMessage' => $note,
            'payeeNote' => $note,
        ];

        $headers = array_merge($this->getDisbursementHeaders(), [
            'X-Reference-Id' => $reference,
        ]);

        $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
            ->post($this->baseUrl . '/disbursement/v1_0/transfer', $payload);

        $this->logTransaction('transfer', [
            'reference' => $reference,
            'external_id' => $externalId,
            'phone' => $phone,
            'amount' => $amount,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        if ($response->status() !== 202) {
            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Transfer failed',
                'reference' => $externalId,
            ];
        }

        return [
            'success' => true,
            'reference' => $externalId,
            'momo_reference' => $reference,
            'message' => 'Transfer initiated successfully',
        ];
    }

    /**
     * Check Transfer status.
     */
    public function checkTransferStatus(string $reference): array
    {
        $response = \Illuminate\Support\Facades\Http::withHeaders($this->getDisbursementHeaders())
            ->get($this->baseUrl . '/disbursement/v1_0/transfer/' . $reference);

        $this->logTransaction('transfer_status', [
            'reference' => $reference,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Status check failed',
            ];
        }

        $data = $response->json();
        $status = $data['status'] ?? 'FAILED';

        return [
            'success' => $status === 'SUCCESSFUL',
            'status' => strtolower($status),
            'data' => $data,
        ];
    }

    /**
     * Get account balance.
     */
    public function getBalance(): array
    {
        $response = \Illuminate\Support\Facades\Http::withHeaders($this->getCollectionHeaders())
            ->get($this->baseUrl . '/collection/v1_0/account/balance');

        return [
            'success' => $response->successful(),
            'balance' => $response->json('availableBalance'),
            'currency' => $response->json('currency'),
        ];
    }

    /**
     * Validate account holder.
     */
    public function validateAccountHolder(string $phone): array
    {
        $phone = $this->formatMomoPhone($phone);

        $response = \Illuminate\Support\Facades\Http::withHeaders($this->getCollectionHeaders())
            ->get($this->baseUrl . '/collection/v1_0/accountholder/msisdn/' . $phone . '/active');

        return [
            'success' => $response->successful(),
            'active' => $response->json('result', false),
        ];
    }

    /**
     * Create sandbox API user (for testing).
     */
    public function createSandboxUser(string $callbackHost): array
    {
        if (($this->config['environment'] ?? 'sandbox') !== 'sandbox') {
            return ['success' => false, 'message' => 'Only available in sandbox'];
        }

        $userId = (string) Str::uuid();

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => $this->config['collection']['subscription_key'],
            'X-Reference-Id' => $userId,
        ])->post($this->baseUrl . '/v1_0/apiuser', [
            'providerCallbackHost' => $callbackHost,
        ]);

        if ($response->status() !== 201) {
            return ['success' => false, 'message' => 'Failed to create user'];
        }

        // Create API key
        $keyResponse = \Illuminate\Support\Facades\Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->config['collection']['subscription_key'],
        ])->post($this->baseUrl . '/v1_0/apiuser/' . $userId . '/apikey');

        return [
            'success' => $keyResponse->successful(),
            'api_user' => $userId,
            'api_key' => $keyResponse->json('apiKey'),
        ];
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): array
    {
        $phone = $payment->phone_number;
        if (!$phone) {
            return ['success' => false, 'message' => 'Phone number not found'];
        }

        return $this->sendMoney(
            $amount ?? $payment->amount,
            $phone,
            $payment->currency,
            $reason ?? 'Refund for payment ' . $payment->reference
        );
    }

    /**
     * Format phone number for MTN MoMo.
     */
    protected function formatMomoPhone(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zeros or country code variations
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            // Need country code context to properly format
            // Assuming Uganda for now
            $phone = '256' . substr($phone, 1);
        }

        return $phone;
    }
}
