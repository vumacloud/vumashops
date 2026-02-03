<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Cache;

class MpesaKenyaGateway extends BaseGateway
{
    protected function getBaseUrl(): string
    {
        $env = $this->config['environment'] ?? 'sandbox';
        return $env === 'production'
            ? ($this->config['production_url'] ?? 'https://api.safaricom.co.ke')
            : ($this->config['sandbox_url'] ?? 'https://sandbox.safaricom.co.ke');
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ];
    }

    public function getIdentifier(): string
    {
        return 'mpesa_kenya';
    }

    public function getName(): string
    {
        return 'M-Pesa Kenya';
    }

    public function getDescription(): string
    {
        return 'Pay with M-Pesa (Kenya)';
    }

    public function getSupportedCountries(): array
    {
        return ['KE'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['KES'];
    }

    /**
     * Get OAuth access token from Safaricom.
     */
    protected function getAccessToken(): string
    {
        $cacheKey = 'mpesa_kenya_access_token';

        return Cache::remember($cacheKey, 3500, function () {
            $credentials = base64_encode(
                $this->config['consumer_key'] . ':' . $this->config['consumer_secret']
            );

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Basic ' . $credentials,
            ])->get($this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials');

            if (!$response->successful()) {
                throw new \Exception('Failed to get M-Pesa access token');
            }

            return $response->json('access_token');
        });
    }

    /**
     * Initialize STK Push (Lipa Na M-Pesa Online).
     */
    public function initialize(float $amount, string $currency, array $customer, array $metadata = []): array
    {
        $reference = $this->generateReference();
        $phone = $this->formatPhoneNumber($customer['phone'] ?? '', '254');
        $timestamp = now()->format('YmdHis');
        $shortcode = $this->config['shortcode'];
        $passkey = $this->config['passkey'];

        $password = base64_encode($shortcode . $passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) ceil($amount), // M-Pesa doesn't accept decimals
            'PartyA' => $phone,
            'PartyB' => $shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->config['callback_url'] ?? route('payment.webhook', ['gateway' => 'mpesa_kenya']),
            'AccountReference' => $metadata['account_reference'] ?? substr($reference, 0, 12),
            'TransactionDesc' => $metadata['description'] ?? 'Payment',
        ];

        $response = $this->makeRequest('post', 'mpesa/stkpush/v1/processrequest', $payload);

        $this->logTransaction('stk_push', [
            'reference' => $reference,
            'phone' => $phone,
            'amount' => $amount,
            'response' => $response,
        ]);

        if (!$response['success']) {
            return [
                'success' => false,
                'message' => $response['data']['errorMessage'] ?? 'STK Push failed',
                'reference' => $reference,
            ];
        }

        $data = $response['data'];
        $responseCode = $data['ResponseCode'] ?? '1';

        if ($responseCode !== '0') {
            return [
                'success' => false,
                'message' => $data['ResponseDescription'] ?? 'STK Push failed',
                'reference' => $reference,
            ];
        }

        return [
            'success' => true,
            'reference' => $reference,
            'checkout_request_id' => $data['CheckoutRequestID'],
            'merchant_request_id' => $data['MerchantRequestID'],
            'message' => 'STK Push sent successfully. Please enter your M-Pesa PIN.',
        ];
    }

    /**
     * Query STK Push status.
     */
    public function verify(string $reference): array
    {
        // For M-Pesa, we query using CheckoutRequestID
        $timestamp = now()->format('YmdHis');
        $shortcode = $this->config['shortcode'];
        $passkey = $this->config['passkey'];

        $password = base64_encode($shortcode . $passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $reference,
        ];

        $response = $this->makeRequest('post', 'mpesa/stkpushquery/v1/query', $payload);

        $this->logTransaction('stk_query', [
            'checkout_request_id' => $reference,
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
        $resultCode = $data['ResultCode'] ?? '1';

        return [
            'success' => $resultCode === '0' || $resultCode === 0,
            'status' => $resultCode === '0' || $resultCode === 0 ? 'success' : 'failed',
            'message' => $data['ResultDesc'] ?? 'Unknown',
            'amount' => $data['Amount'] ?? null,
            'mpesa_receipt' => $data['MpesaReceiptNumber'] ?? null,
            'phone' => $data['PhoneNumber'] ?? null,
            'data' => $data,
        ];
    }

    /**
     * Handle M-Pesa callback.
     */
    public function handleWebhook(array $payload): array
    {
        $this->logTransaction('callback', $payload);

        $body = $payload['Body'] ?? $payload;
        $stkCallback = $body['stkCallback'] ?? $body;

        $resultCode = $stkCallback['ResultCode'] ?? 1;
        $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;

        if ($resultCode === 0) {
            $callbackMetadata = $stkCallback['CallbackMetadata']['Item'] ?? [];
            $metadata = [];

            foreach ($callbackMetadata as $item) {
                $metadata[$item['Name']] = $item['Value'] ?? null;
            }

            return [
                'valid' => true,
                'reference' => $checkoutRequestId,
                'status' => 'success',
                'amount' => $metadata['Amount'] ?? 0,
                'mpesa_receipt' => $metadata['MpesaReceiptNumber'] ?? null,
                'phone' => $metadata['PhoneNumber'] ?? null,
                'transaction_date' => $metadata['TransactionDate'] ?? null,
                'data' => $stkCallback,
            ];
        }

        return [
            'valid' => true,
            'reference' => $checkoutRequestId,
            'status' => 'failed',
            'message' => $stkCallback['ResultDesc'] ?? 'Transaction failed',
            'data' => $stkCallback,
        ];
    }

    /**
     * Register C2B URLs.
     */
    public function registerUrls(string $confirmationUrl, string $validationUrl): array
    {
        $payload = [
            'ShortCode' => $this->config['shortcode'],
            'ResponseType' => 'Completed',
            'ConfirmationURL' => $confirmationUrl,
            'ValidationURL' => $validationUrl,
        ];

        $response = $this->makeRequest('post', 'mpesa/c2b/v1/registerurl', $payload);

        return [
            'success' => $response['success'] && ($response['data']['ResponseCode'] ?? '1') === '0',
            'data' => $response['data'],
        ];
    }

    /**
     * Simulate C2B payment (sandbox only).
     */
    public function simulateC2B(float $amount, string $phone, string $billRefNumber): array
    {
        if (($this->config['environment'] ?? 'sandbox') !== 'sandbox') {
            return ['success' => false, 'message' => 'C2B simulation only available in sandbox'];
        }

        $payload = [
            'ShortCode' => $this->config['shortcode'],
            'CommandID' => 'CustomerPayBillOnline',
            'Amount' => (int) ceil($amount),
            'Msisdn' => $this->formatPhoneNumber($phone, '254'),
            'BillRefNumber' => $billRefNumber,
        ];

        $response = $this->makeRequest('post', 'mpesa/c2b/v1/simulate', $payload);

        return [
            'success' => $response['success'] && ($response['data']['ResponseCode'] ?? '1') === '0',
            'data' => $response['data'],
        ];
    }

    /**
     * B2C payment (business to customer).
     */
    public function sendMoney(float $amount, string $phone, string $commandId = 'BusinessPayment', string $remarks = 'Payment'): array
    {
        $payload = [
            'InitiatorName' => $this->config['initiator_name'],
            'SecurityCredential' => $this->getSecurityCredential(),
            'CommandID' => $commandId, // SalaryPayment, BusinessPayment, PromotionPayment
            'Amount' => (int) ceil($amount),
            'PartyA' => $this->config['shortcode'],
            'PartyB' => $this->formatPhoneNumber($phone, '254'),
            'Remarks' => $remarks,
            'QueueTimeOutURL' => $this->config['timeout_url'],
            'ResultURL' => $this->config['result_url'],
            'Occasion' => '',
        ];

        $response = $this->makeRequest('post', 'mpesa/b2c/v1/paymentrequest', $payload);

        $this->logTransaction('b2c', [
            'phone' => $phone,
            'amount' => $amount,
            'response' => $response,
        ]);

        return [
            'success' => $response['success'] && ($response['data']['ResponseCode'] ?? '1') === '0',
            'conversation_id' => $response['data']['ConversationID'] ?? null,
            'originator_conversation_id' => $response['data']['OriginatorConversationID'] ?? null,
            'data' => $response['data'],
        ];
    }

    /**
     * Check account balance.
     */
    public function checkBalance(): array
    {
        $payload = [
            'Initiator' => $this->config['initiator_name'],
            'SecurityCredential' => $this->getSecurityCredential(),
            'CommandID' => 'AccountBalance',
            'PartyA' => $this->config['shortcode'],
            'IdentifierType' => '4',
            'Remarks' => 'Balance check',
            'QueueTimeOutURL' => $this->config['timeout_url'],
            'ResultURL' => $this->config['result_url'],
        ];

        $response = $this->makeRequest('post', 'mpesa/accountbalance/v1/query', $payload);

        return [
            'success' => $response['success'] && ($response['data']['ResponseCode'] ?? '1') === '0',
            'data' => $response['data'],
        ];
    }

    /**
     * Check transaction status.
     */
    public function checkTransactionStatus(string $transactionId): array
    {
        $payload = [
            'Initiator' => $this->config['initiator_name'],
            'SecurityCredential' => $this->getSecurityCredential(),
            'CommandID' => 'TransactionStatusQuery',
            'TransactionID' => $transactionId,
            'PartyA' => $this->config['shortcode'],
            'IdentifierType' => '4',
            'ResultURL' => $this->config['result_url'],
            'QueueTimeOutURL' => $this->config['timeout_url'],
            'Remarks' => 'Transaction status query',
            'Occasion' => '',
        ];

        $response = $this->makeRequest('post', 'mpesa/transactionstatus/v1/query', $payload);

        return [
            'success' => $response['success'] && ($response['data']['ResponseCode'] ?? '1') === '0',
            'data' => $response['data'],
        ];
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): array
    {
        // M-Pesa doesn't have direct refund API, use B2C instead
        $phone = $payment->phone_number;
        if (!$phone) {
            return ['success' => false, 'message' => 'Phone number not found'];
        }

        return $this->sendMoney(
            $amount ?? $payment->amount,
            $phone,
            'BusinessPayment',
            $reason ?? 'Refund for payment ' . $payment->reference
        );
    }

    /**
     * Get security credential for B2C/B2B transactions.
     */
    protected function getSecurityCredential(): string
    {
        // In production, this should encrypt the initiator password with the M-Pesa certificate
        // For sandbox, you can use the plain password or a pre-generated credential
        return $this->config['initiator_password'] ?? '';
    }
}
