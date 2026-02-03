<?php

namespace App\Contracts;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Get the gateway identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get the gateway display name.
     */
    public function getName(): string;

    /**
     * Get the gateway description.
     */
    public function getDescription(): string;

    /**
     * Check if the gateway is available for the given country/currency.
     */
    public function isAvailable(string $country, string $currency): bool;

    /**
     * Initialize a payment.
     *
     * @param float $amount
     * @param string $currency
     * @param array $customer
     * @param array $metadata
     * @return array Contains 'reference', 'authorization_url' or 'ussd_code', etc.
     */
    public function initialize(float $amount, string $currency, array $customer, array $metadata = []): array;

    /**
     * Verify a payment by reference.
     *
     * @param string $reference
     * @return array Contains 'success', 'status', 'amount', 'data'
     */
    public function verify(string $reference): array;

    /**
     * Handle webhook callback.
     *
     * @param array $payload
     * @return array Contains 'valid', 'reference', 'status', 'data'
     */
    public function handleWebhook(array $payload): array;

    /**
     * Refund a payment.
     *
     * @param Payment $payment
     * @param float|null $amount
     * @param string|null $reason
     * @return array Contains 'success', 'reference', 'data'
     */
    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): array;

    /**
     * Get supported countries.
     */
    public function getSupportedCountries(): array;

    /**
     * Get supported currencies.
     */
    public function getSupportedCurrencies(): array;

    /**
     * Check if the gateway supports refunds.
     */
    public function supportsRefunds(): bool;

    /**
     * Check if the gateway supports recurring payments.
     */
    public function supportsRecurring(): bool;

    /**
     * Get the gateway configuration.
     */
    public function getConfig(): array;
}
