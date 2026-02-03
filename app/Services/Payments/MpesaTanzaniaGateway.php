<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Cache;

class MpesaTanzaniaGateway extends BaseGateway
{
    protected function getBaseUrl(): string
    {
        $env = $this->config['environment'] ?? 'sandbox';
        return $env === 'production'
            ? ($this->config['production_url'] ?? 'https://openapi.m-pesa.com:8443')
            : ($this->config['sandbox_url'] ?? 'https://openapi.m-pesa.com:8443/sandbox');
    }

    public function getIdentifier(): string
    {
        return 'mpesa_tanzania';
    }

    public function getName(): string
    {
        return 'M-Pesa Tanzania';
    }

    public function getDescription(): string
    {
        return 'Pay with M-Pesa (Tanzania)';
    }

    public function getSupportedCountries(): array
    {
        return ['TZ'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['TZS'];
    }

    /**
     * Get encrypted API key for Vodacom M-Pesa Tanzania.
     */
    protected function getEncryptedApiKey(): string
    {
        $cacheKey = 'mpesa_tz_encrypted_key';

        return Cache::remember($cacheKey, 3600, function () {
            $publicKey = $this->config['public_key'];
            $apiKey = $this->config['api_key'];

            // Create public key resource
            $publicKeyResource = openssl_pkey_get_public($this->formatPublicKey($publicKey));

            if (!$publicKeyResource) {
                throw new \Exception('Invalid public key');
            }

            // Encrypt the API key
            $encrypted = '';
            openssl_public_encrypt($apiKey, $encrypted, $publicKeyResource, OPENSSL_PKCS1_PADDING);

            return base64_encode($encrypted);
        });
    }

    /**
     * Format the public key with proper headers.
     */
    protected function formatPublicKey(string $key): string
    {
        $key = trim($key);

        if (!str_starts_with($key, '-----BEGIN PUBLIC KEY-----')) {
            $key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($key, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        }

        return $key;
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getEncryptedApiKey(),
            'Origin' => '*',
        ];
    }

    /**
     * Initialize Customer to Business (C2B) payment.
     */
    public function initialize(float $amount, string $currency, array $customer, array $metadata = []): array
    {
        $reference = $this->generateReference();
        $phone = $this->formatPhoneNumber($customer['phone'] ?? '', '255');

        $payload = [
            'input_Amount' => (string) ceil($amount),
            'input_Country' => 'TZN',
            'input_Currency' => 'TZS',
            'input_CustomerMSISDN' => $phone,
            'input_ServiceProviderCode' => $this->config['service_provider_code'],
            'input_ThirdPartyConversationID' => $reference,
            'input_TransactionReference' => substr($reference, 0, 20),
            'input_PurchasedItemsDesc' => $metadata['description'] ?? 'Payment',
        ];

        $response = $this->makeRequest('post', 'ipg/v2/vodacomTZN/c2bPayment/singleStage/', $payload);

        $this->logTransaction('c2b_single_stage', [
            'reference' => $reference,
            'phone' => $phone,
            'amount' => $amount,
            'response' => $response,
        ]);

        if (!$response['success']) {
            return [
                'success' => false,
                'message' => $response['data']['output_ResponseDesc'] ?? 'Payment initiation failed',
                'reference' => $reference,
            ];
        }

        $data = $response['data'];
        $responseCode = $data['output_ResponseCode'] ?? 'INS-1';

        if ($responseCode !== 'INS-0') {
            return [
                'success' => false,
                'message' => $data['output_ResponseDesc'] ?? 'Payment initiation failed',
                'reference' => $reference,
                'code' => $responseCode,
            ];
        }

        return [
            'success' => true,
            'reference' => $reference,
            'conversation_id' => $data['output_ConversationID'] ?? null,
            'transaction_id' => $data['output_TransactionID'] ?? null,
            'message' => 'Payment request sent. Please confirm on your phone.',
        ];
    }

    /**
     * Query transaction status.
     */
    public function verify(string $reference): array
    {
        $payload = [
            'input_QueryReference' => $reference,
            'input_ServiceProviderCode' => $this->config['service_provider_code'],
            'input_ThirdPartyConversationID' => $this->generateReference(),
            'input_Country' => 'TZN',
        ];

        $response = $this->makeRequest('post', 'ipg/v2/vodacomTZN/queryTransactionStatus/', $payload);

        $this->logTransaction('query_status', [
            'reference' => $reference,
            'response' => $response,
        ]);

        if (!$response['success']) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Query failed',
                'data' => $response['data'],
            ];
        }

        $data = $response['data'];
        $responseCode = $data['output_ResponseCode'] ?? 'INS-1';

        return [
            'success' => $responseCode === 'INS-0',
            'status' => $responseCode === 'INS-0' ? 'success' : 'pending',
            'message' => $data['output_ResponseDesc'] ?? 'Unknown',
            'conversation_id' => $data['output_ConversationID'] ?? null,
            'transaction_id' => $data['output_OriginalTransactionID'] ?? null,
            'data' => $data,
        ];
    }

    /**
     * Handle webhook callback.
     */
    public function handleWebhook(array $payload): array
    {
        $this->logTransaction('callback', $payload);

        $responseCode = $payload['output_ResponseCode'] ?? 'INS-1';
        $conversationId = $payload['output_ConversationID'] ?? null;
        $transactionId = $payload['output_TransactionID'] ?? null;
        $thirdPartyId = $payload['output_ThirdPartyConversationID'] ?? null;

        return [
            'valid' => true,
            'reference' => $thirdPartyId,
            'status' => $responseCode === 'INS-0' ? 'success' : 'failed',
            'message' => $payload['output_ResponseDesc'] ?? '',
            'conversation_id' => $conversationId,
            'transaction_id' => $transactionId,
            'data' => $payload,
        ];
    }

    /**
     * Business to Customer (B2C) payment.
     */
    public function sendMoney(float $amount, string $phone, string $remarks = 'Payment'): array
    {
        $reference = $this->generateReference();
        $phone = $this->formatPhoneNumber($phone, '255');

        $payload = [
            'input_Amount' => (string) ceil($amount),
            'input_Country' => 'TZN',
            'input_Currency' => 'TZS',
            'input_CustomerMSISDN' => $phone,
            'input_ServiceProviderCode' => $this->config['service_provider_code'],
            'input_ThirdPartyConversationID' => $reference,
            'input_TransactionReference' => substr($reference, 0, 20),
            'input_PaymentItemsDesc' => $remarks,
        ];

        $response = $this->makeRequest('post', 'ipg/v2/vodacomTZN/b2cPayment/', $payload);

        $this->logTransaction('b2c', [
            'reference' => $reference,
            'phone' => $phone,
            'amount' => $amount,
            'response' => $response,
        ]);

        $data = $response['data'] ?? [];
        $responseCode = $data['output_ResponseCode'] ?? 'INS-1';

        return [
            'success' => $responseCode === 'INS-0',
            'reference' => $reference,
            'conversation_id' => $data['output_ConversationID'] ?? null,
            'transaction_id' => $data['output_TransactionID'] ?? null,
            'message' => $data['output_ResponseDesc'] ?? 'Unknown',
            'data' => $data,
        ];
    }

    /**
     * Business to Business (B2B) payment.
     */
    public function b2bPayment(float $amount, string $receiverCode, string $remarks = 'Payment'): array
    {
        $reference = $this->generateReference();

        $payload = [
            'input_Amount' => (string) ceil($amount),
            'input_Country' => 'TZN',
            'input_Currency' => 'TZS',
            'input_PrimaryPartyCode' => $this->config['service_provider_code'],
            'input_ReceiverPartyCode' => $receiverCode,
            'input_ThirdPartyConversationID' => $reference,
            'input_TransactionReference' => substr($reference, 0, 20),
            'input_PurchasedItemsDesc' => $remarks,
        ];

        $response = $this->makeRequest('post', 'ipg/v2/vodacomTZN/b2bPayment/', $payload);

        $this->logTransaction('b2b', [
            'reference' => $reference,
            'receiver' => $receiverCode,
            'amount' => $amount,
            'response' => $response,
        ]);

        $data = $response['data'] ?? [];
        $responseCode = $data['output_ResponseCode'] ?? 'INS-1';

        return [
            'success' => $responseCode === 'INS-0',
            'reference' => $reference,
            'conversation_id' => $data['output_ConversationID'] ?? null,
            'transaction_id' => $data['output_TransactionID'] ?? null,
            'data' => $data,
        ];
    }

    /**
     * Reverse a transaction.
     */
    public function reverseTransaction(string $transactionId, float $amount): array
    {
        $reference = $this->generateReference();

        $payload = [
            'input_ReversalAmount' => (string) ceil($amount),
            'input_Country' => 'TZN',
            'input_ServiceProviderCode' => $this->config['service_provider_code'],
            'input_ThirdPartyConversationID' => $reference,
            'input_TransactionID' => $transactionId,
        ];

        $response = $this->makeRequest('post', 'ipg/v2/vodacomTZN/reversal/', $payload);

        $this->logTransaction('reversal', [
            'reference' => $reference,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'response' => $response,
        ]);

        $data = $response['data'] ?? [];
        $responseCode = $data['output_ResponseCode'] ?? 'INS-1';

        return [
            'success' => $responseCode === 'INS-0',
            'reference' => $reference,
            'data' => $data,
        ];
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): array
    {
        if (!$payment->gateway_reference) {
            return ['success' => false, 'message' => 'Transaction ID not found'];
        }

        return $this->reverseTransaction(
            $payment->gateway_reference,
            $amount ?? $payment->amount
        );
    }

    public function supportsRefunds(): bool
    {
        return true;
    }
}
