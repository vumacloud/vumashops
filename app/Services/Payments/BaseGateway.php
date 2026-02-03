<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseGateway implements PaymentGatewayInterface
{
    protected array $config;
    protected string $baseUrl;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->baseUrl = $this->getBaseUrl();
    }

    abstract protected function getBaseUrl(): string;

    public function getConfig(): array
    {
        return $this->config;
    }

    protected function makeRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        try {
            $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

            $defaultHeaders = $this->getDefaultHeaders();
            $headers = array_merge($defaultHeaders, $headers);

            $response = Http::withHeaders($headers)
                ->timeout(config('payments.timeout', 30))
                ->$method($url, $data);

            $responseData = $response->json();

            if (!$response->successful()) {
                Log::error("Payment gateway request failed", [
                    'gateway' => $this->getIdentifier(),
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
            Log::error("Payment gateway request exception", [
                'gateway' => $this->getIdentifier(),
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

    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function isAvailable(string $country, string $currency): bool
    {
        return in_array($country, $this->getSupportedCountries())
            && in_array($currency, $this->getSupportedCurrencies());
    }

    public function supportsRefunds(): bool
    {
        return false;
    }

    public function supportsRecurring(): bool
    {
        return false;
    }

    protected function generateReference(): string
    {
        return Payment::generateReference();
    }

    protected function logTransaction(string $type, array $data): void
    {
        Log::channel('payments')->info("Payment {$type}", [
            'gateway' => $this->getIdentifier(),
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function formatPhoneNumber(string $phone, string $countryCode = ''): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Handle different country formats
        if (str_starts_with($phone, '0')) {
            $phone = $countryCode . substr($phone, 1);
        } elseif (!str_starts_with($phone, $countryCode) && strlen($phone) <= 10) {
            $phone = $countryCode . $phone;
        }

        return $phone;
    }

    protected function validateAmount(float $amount, string $currency): bool
    {
        $minimums = [
            'NGN' => 100,
            'KES' => 10,
            'GHS' => 1,
            'TZS' => 1000,
            'UGX' => 1000,
            'ZAR' => 1,
            'USD' => 1,
            'EUR' => 1,
        ];

        $minimum = $minimums[$currency] ?? 1;
        return $amount >= $minimum;
    }
}
