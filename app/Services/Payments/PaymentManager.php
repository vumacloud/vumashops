<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use App\Models\Tenant;
use InvalidArgumentException;

class PaymentManager
{
    protected array $gateways = [];
    protected array $config;

    public function __construct()
    {
        $this->config = config('payments.gateways', []);
        $this->registerGateways();
    }

    /**
     * Register all configured payment gateways.
     */
    protected function registerGateways(): void
    {
        foreach ($this->config as $identifier => $gatewayConfig) {
            if (!($gatewayConfig['enabled'] ?? false)) {
                continue;
            }

            $driverClass = $gatewayConfig['driver'] ?? null;

            if ($driverClass && class_exists($driverClass)) {
                $this->gateways[$identifier] = new $driverClass($gatewayConfig);
            }
        }
    }

    /**
     * Get a payment gateway instance.
     */
    public function gateway(string $identifier): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$identifier])) {
            throw new InvalidArgumentException("Payment gateway [{$identifier}] is not configured or enabled.");
        }

        return $this->gateways[$identifier];
    }

    /**
     * Get the default payment gateway.
     */
    public function getDefaultGateway(): PaymentGatewayInterface
    {
        $default = config('payments.default', 'paystack');
        return $this->gateway($default);
    }

    /**
     * Get all available gateways.
     */
    public function getAvailableGateways(): array
    {
        return $this->gateways;
    }

    /**
     * Get gateways available for a specific country and currency.
     */
    public function getGatewaysForLocation(string $country, string $currency): array
    {
        return array_filter($this->gateways, function ($gateway) use ($country, $currency) {
            return $gateway->isAvailable($country, $currency);
        });
    }

    /**
     * Get gateways available for a tenant.
     */
    public function getGatewaysForTenant(Tenant $tenant): array
    {
        $country = $tenant->country ?? 'KE';
        $currency = $tenant->currency ?? 'KES';

        // Get tenant's enabled payment methods
        $enabledMethods = $tenant->paymentMethods()
            ->where('is_active', true)
            ->pluck('gateway')
            ->toArray();

        $availableGateways = $this->getGatewaysForLocation($country, $currency);

        if (empty($enabledMethods)) {
            return $availableGateways;
        }

        return array_filter($availableGateways, function ($gateway, $identifier) use ($enabledMethods) {
            return in_array($identifier, $enabledMethods);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Initialize a payment using the specified gateway.
     */
    public function initializePayment(
        string $gatewayIdentifier,
        float $amount,
        string $currency,
        array $customer,
        array $metadata = []
    ): array {
        $gateway = $this->gateway($gatewayIdentifier);

        $result = $gateway->initialize($amount, $currency, $customer, $metadata);

        if ($result['success']) {
            // Create payment record
            $payment = Payment::create([
                'tenant_id' => $metadata['tenant_id'] ?? null,
                'order_id' => $metadata['order_id'] ?? null,
                'customer_id' => $metadata['customer_id'] ?? null,
                'reference' => $result['reference'],
                'gateway' => $gatewayIdentifier,
                'method' => $gatewayIdentifier,
                'amount' => $amount,
                'currency' => $currency,
                'email' => $customer['email'] ?? null,
                'phone_number' => $customer['phone'] ?? null,
                'description' => $metadata['description'] ?? null,
                'metadata' => $metadata,
            ]);

            $result['payment_id'] = $payment->id;
        }

        return $result;
    }

    /**
     * Verify a payment.
     */
    public function verifyPayment(string $gatewayIdentifier, string $reference): array
    {
        $gateway = $this->gateway($gatewayIdentifier);
        $result = $gateway->verify($reference);

        if ($result['success']) {
            // Update payment record
            $payment = Payment::where('reference', $reference)->first();

            if ($payment) {
                $payment->markAsCompleted(
                    $result['gateway_reference'] ?? null,
                    $result['data'] ?? []
                );
            }
        }

        return $result;
    }

    /**
     * Handle webhook for a gateway.
     */
    public function handleWebhook(string $gatewayIdentifier, array $payload): array
    {
        $gateway = $this->gateway($gatewayIdentifier);
        $result = $gateway->handleWebhook($payload);

        if ($result['valid'] && !empty($result['reference'])) {
            $payment = Payment::where('reference', $result['reference'])
                ->orWhere('gateway_reference', $result['reference'])
                ->first();

            if ($payment) {
                if ($result['status'] === 'success') {
                    $payment->markAsCompleted($result['transaction_id'] ?? null, $result['data'] ?? []);
                } elseif ($result['status'] === 'failed') {
                    $payment->markAsFailed($result['message'] ?? null, $result['data'] ?? []);
                }
            }
        }

        return $result;
    }

    /**
     * Refund a payment.
     */
    public function refundPayment(Payment $payment, ?float $amount = null, ?string $reason = null): array
    {
        $gateway = $this->gateway($payment->gateway);

        if (!$gateway->supportsRefunds()) {
            return [
                'success' => false,
                'message' => 'This payment gateway does not support refunds.',
            ];
        }

        $result = $gateway->refund($payment, $amount, $reason);

        if ($result['success']) {
            $payment->markAsRefunded($amount);
        }

        return $result;
    }

    /**
     * Get list of all gateway configurations for display.
     */
    public function getGatewayConfigurations(): array
    {
        $configurations = [];

        foreach ($this->gateways as $identifier => $gateway) {
            $configurations[$identifier] = [
                'identifier' => $identifier,
                'name' => $gateway->getName(),
                'description' => $gateway->getDescription(),
                'countries' => $gateway->getSupportedCountries(),
                'currencies' => $gateway->getSupportedCurrencies(),
                'supports_refunds' => $gateway->supportsRefunds(),
                'supports_recurring' => $gateway->supportsRecurring(),
            ];
        }

        return $configurations;
    }

    /**
     * Check if a specific gateway is available.
     */
    public function hasGateway(string $identifier): bool
    {
        return isset($this->gateways[$identifier]);
    }

    /**
     * Get recommended gateway for a country.
     */
    public function getRecommendedGateway(string $country, string $currency): ?string
    {
        // Priority order for different regions
        $priorities = [
            'KE' => ['mpesa_kenya', 'paystack', 'flutterwave'],
            'TZ' => ['mpesa_tanzania', 'flutterwave', 'airtel_money'],
            'UG' => ['mtn_momo', 'airtel_money', 'flutterwave'],
            'NG' => ['paystack', 'flutterwave'],
            'GH' => ['paystack', 'mtn_momo', 'flutterwave'],
            'ZA' => ['paystack', 'flutterwave'],
            'RW' => ['mtn_momo', 'flutterwave'],
            'ZM' => ['mtn_momo', 'airtel_money', 'flutterwave'],
        ];

        $gatewayPriority = $priorities[$country] ?? ['flutterwave', 'paystack'];

        foreach ($gatewayPriority as $gateway) {
            if ($this->hasGateway($gateway)) {
                $instance = $this->gateway($gateway);
                if ($instance->isAvailable($country, $currency)) {
                    return $gateway;
                }
            }
        }

        return null;
    }

    /**
     * Get mobile money gateways.
     */
    public function getMobileMoneyGateways(): array
    {
        $mobileMoneyGateways = ['mpesa_kenya', 'mpesa_tanzania', 'mtn_momo', 'airtel_money'];

        return array_filter($this->gateways, function ($gateway, $identifier) use ($mobileMoneyGateways) {
            return in_array($identifier, $mobileMoneyGateways);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Get card payment gateways.
     */
    public function getCardGateways(): array
    {
        $cardGateways = ['paystack', 'flutterwave'];

        return array_filter($this->gateways, function ($gateway, $identifier) use ($cardGateways) {
            return in_array($identifier, $cardGateways);
        }, ARRAY_FILTER_USE_BOTH);
    }
}
