<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Cache;

class AirtelMoneyGateway extends BaseGateway
{
    protected function getBaseUrl(): string
    {
        $env = $this->config['environment'] ?? 'sandbox';
        return $env === 'production'
            ? ($this->config['production_url'] ?? 'https://openapi.airtel.africa')
            : ($this->config['sandbox_url'] ?? 'https://openapiuat.airtel.africa');
    }

    public function getIdentifier(): string
    {
        return 'airtel_money';
    }

    public function getName(): string
    {
        return 'Airtel Money';
    }

    public function getDescription(): string
    {
        return 'Pay with Airtel Money';
    }

    public function getSupportedCountries(): array
    {
        return $this->config['countries'] ?? [
            'UG', 'KE', 'TZ', 'RW', 'ZM', 'MW', 'NG', 'CG', 'CD', 'MG', 'NE', 'TD', 'GA', 'SL'
        ];
    }

    public function getSupportedCurrencies(): array
    {
        return $this->config['currencies'] ?? [
            'UGX', 'KES', 'TZS', 'RWF', 'ZMW', 'MWK', 'NGN', 'XAF', 'CDF', 'MGA', 'XOF', 'SLL'
        ];
    }

    /**
     * Get OAuth access token.
     */
    protected function getAccessToken(): string
    {
        $cacheKey = 'airtel_money_access_token';

        return Cache::remember($cacheKey, 3500, function () {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => '*/*',
            ])->post($this->baseUrl . '/auth/oauth2/token', [
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'grant_type' => 'client_credentials',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to get Airtel Money access token: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => '*/*',
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'X-Country' => $this->config['country'] ?? 'UG',
            'X-Currency' => $this->config['currency'] ?? 'UGX',
        ];
    }

    /**
     * Request Payment (USSD Push).
     */
    public function initialize(float $amount, string $currency, array $customer, array $metadata = []): array
    {
        $reference = $this->generateReference();
        $phone = $this->formatAirtelPhone($customer['phone'] ?? '');
        $country = $metadata['country'] ?? $this->config['country'] ?? 'UG';

        $payload = [
            'reference' => $reference,
            'subscriber' => [
                'country' => $country,
                'currency' => $currency ?: ($this->config['currency'] ?? 'UGX'),
                'msisdn' => $phone,
            ],
            'transaction' => [
                'amount' => $amount,
                'country' => $country,
                'currency' => $currency ?: ($this->config['currency'] ?? 'UGX'),
                'id' => $reference,
            ],
        ];

        $headers = $this->getDefaultHeaders();
        $headers['X-Country'] = $country;
        $headers['X-Currency'] = $currency ?: ($this->config['currency'] ?? 'UGX');

        $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
            ->post($this->baseUrl . '/merchant/v1/payments/', $payload);

        $this->logTransaction('payment_request', [
            'reference' => $reference,
            'phone' => $phone,
            'amount' => $amount,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        $data = $response->json();

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => $data['status']['message'] ?? 'Payment request failed',
                'reference' => $reference,
            ];
        }

        $status = $data['status']['response_code'] ?? '';

        if ($status !== 'DP00800001001') { // Success code
            return [
                'success' => false,
                'message' => $data['status']['message'] ?? 'Payment request failed',
                'reference' => $reference,
                'code' => $status,
            ];
        }

        return [
            'success' => true,
            'reference' => $reference,
            'airtel_reference' => $data['data']['transaction']['id'] ?? null,
            'message' => 'Payment request sent. Please enter your PIN to confirm.',
        ];
    }

    /**
     * Check payment status.
     */
    public function verify(string $reference): array
    {
        $headers = $this->getDefaultHeaders();

        $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
            ->get($this->baseUrl . '/standard/v1/payments/' . $reference);

        $this->logTransaction('verify', [
            'reference' => $reference,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        $data = $response->json();

        if (!$response->successful()) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Verification failed',
                'data' => $data,
            ];
        }

        $transactionData = $data['data']['transaction'] ?? [];
        $status = $transactionData['status'] ?? 'TS';

        // Status codes: TIP (In Progress), TF (Failed), TS (Success)
        $statusMap = [
            'TS' => 'success',
            'TIP' => 'pending',
            'TF' => 'failed',
        ];

        return [
            'success' => $status === 'TS',
            'status' => $statusMap[$status] ?? 'unknown',
            'amount' => $transactionData['amount'] ?? 0,
            'airtel_reference' => $transactionData['airtel_money_id'] ?? null,
            'message' => $transactionData['message'] ?? null,
            'data' => $transactionData,
        ];
    }

    /**
     * Handle webhook callback.
     */
    public function handleWebhook(array $payload): array
    {
        $this->logTransaction('callback', $payload);

        $transaction = $payload['transaction'] ?? [];
        $status = $transaction['status_code'] ?? 'TF';
        $reference = $transaction['id'] ?? null;

        $statusMap = [
            'TS' => 'success',
            'TIP' => 'pending',
            'TF' => 'failed',
        ];

        return [
            'valid' => true,
            'reference' => $reference,
            'status' => $statusMap[$status] ?? 'unknown',
            'amount' => $transaction['amount'] ?? 0,
            'airtel_reference' => $transaction['airtel_money_id'] ?? null,
            'data' => $payload,
        ];
    }

    /**
     * Disbursement (Send Money).
     */
    public function sendMoney(float $amount, string $phone, string $currency = null, string $country = null): array
    {
        $reference = $this->generateReference();
        $phone = $this->formatAirtelPhone($phone);
        $currency = $currency ?: ($this->config['currency'] ?? 'UGX');
        $country = $country ?: ($this->config['country'] ?? 'UG');

        $payload = [
            'payee' => [
                'msisdn' => $phone,
            ],
            'reference' => $reference,
            'pin' => $this->encryptPin(), // PIN encryption required for disbursement
            'transaction' => [
                'amount' => $amount,
                'id' => $reference,
            ],
        ];

        $headers = $this->getDefaultHeaders();
        $headers['X-Country'] = $country;
        $headers['X-Currency'] = $currency;

        $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
            ->post($this->baseUrl . '/standard/v1/disbursements/', $payload);

        $this->logTransaction('disbursement', [
            'reference' => $reference,
            'phone' => $phone,
            'amount' => $amount,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        $data = $response->json();

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => $data['status']['message'] ?? 'Disbursement failed',
                'reference' => $reference,
            ];
        }

        $status = $data['status']['response_code'] ?? '';

        return [
            'success' => $status === 'DP00800001001',
            'reference' => $reference,
            'airtel_reference' => $data['data']['transaction']['id'] ?? null,
            'message' => $data['status']['message'] ?? 'Disbursement initiated',
            'data' => $data,
        ];
    }

    /**
     * Check disbursement status.
     */
    public function checkDisbursementStatus(string $reference): array
    {
        $headers = $this->getDefaultHeaders();

        $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
            ->get($this->baseUrl . '/standard/v1/disbursements/' . $reference);

        $this->logTransaction('disbursement_status', [
            'reference' => $reference,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        $data = $response->json();

        if (!$response->successful()) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Status check failed',
            ];
        }

        $transactionData = $data['data']['transaction'] ?? [];
        $status = $transactionData['status'] ?? 'TF';

        $statusMap = [
            'TS' => 'success',
            'TIP' => 'pending',
            'TF' => 'failed',
        ];

        return [
            'success' => $status === 'TS',
            'status' => $statusMap[$status] ?? 'unknown',
            'data' => $transactionData,
        ];
    }

    /**
     * Get user info (KYC).
     */
    public function getUserInfo(string $phone): array
    {
        $phone = $this->formatAirtelPhone($phone);
        $headers = $this->getDefaultHeaders();

        $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
            ->get($this->baseUrl . '/standard/v1/users/' . $phone);

        $data = $response->json();

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => $data['status']['message'] ?? 'User lookup failed',
            ];
        }

        return [
            'success' => true,
            'name' => ($data['data']['first_name'] ?? '') . ' ' . ($data['data']['last_name'] ?? ''),
            'first_name' => $data['data']['first_name'] ?? null,
            'last_name' => $data['data']['last_name'] ?? null,
            'is_barred' => $data['data']['is_barred'] ?? false,
            'is_pin_set' => $data['data']['is_pin_set'] ?? false,
            'grade' => $data['data']['grade'] ?? null,
            'data' => $data['data'] ?? [],
        ];
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): array
    {
        // Airtel Money refund via disbursement
        $phone = $payment->phone_number;
        if (!$phone) {
            return ['success' => false, 'message' => 'Phone number not found'];
        }

        return $this->sendMoney(
            $amount ?? $payment->amount,
            $phone,
            $payment->currency
        );
    }

    /**
     * Format phone number for Airtel.
     */
    protected function formatAirtelPhone(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zeros
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        // Remove country code if present (we'll add it back via headers)
        $countryCodes = ['256', '254', '255', '250', '260', '265', '234', '242', '243', '261', '227', '235', '241', '232'];
        foreach ($countryCodes as $code) {
            if (str_starts_with($phone, $code)) {
                $phone = substr($phone, strlen($code));
                break;
            }
        }

        return $phone;
    }

    /**
     * Encrypt PIN for disbursement (placeholder - implement based on Airtel docs).
     */
    protected function encryptPin(): string
    {
        // This would need to be implemented based on Airtel's encryption requirements
        // Usually involves RSA encryption with their public key
        return '';
    }

    /**
     * Get country-specific configuration.
     */
    public function getCountryConfig(string $countryCode): array
    {
        $countries = [
            'UG' => ['currency' => 'UGX', 'name' => 'Uganda'],
            'KE' => ['currency' => 'KES', 'name' => 'Kenya'],
            'TZ' => ['currency' => 'TZS', 'name' => 'Tanzania'],
            'RW' => ['currency' => 'RWF', 'name' => 'Rwanda'],
            'ZM' => ['currency' => 'ZMW', 'name' => 'Zambia'],
            'MW' => ['currency' => 'MWK', 'name' => 'Malawi'],
            'NG' => ['currency' => 'NGN', 'name' => 'Nigeria'],
            'CG' => ['currency' => 'XAF', 'name' => 'Congo Brazzaville'],
            'CD' => ['currency' => 'CDF', 'name' => 'DR Congo'],
            'MG' => ['currency' => 'MGA', 'name' => 'Madagascar'],
            'NE' => ['currency' => 'XOF', 'name' => 'Niger'],
            'TD' => ['currency' => 'XAF', 'name' => 'Chad'],
            'GA' => ['currency' => 'XAF', 'name' => 'Gabon'],
            'SL' => ['currency' => 'SLL', 'name' => 'Sierra Leone'],
        ];

        return $countries[$countryCode] ?? ['currency' => 'USD', 'name' => 'Unknown'];
    }
}
