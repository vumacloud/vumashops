<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Cloudflare DNS Management Service
 *
 * Automates DNS configuration for vendor custom domains.
 * VumaShops by VumaCloud - All vendors use custom domains only.
 */
class CloudflareDnsService
{
    protected string $apiToken;
    protected string $zoneId;
    protected string $accountId;
    protected string $baseUrl = 'https://api.cloudflare.com/client/v4';
    protected string $serverIp;

    public function __construct()
    {
        $this->apiToken = config('services.cloudflare.api_token');
        $this->zoneId = config('services.cloudflare.zone_id');
        $this->accountId = config('services.cloudflare.account_id');
        $this->serverIp = config('services.cloudflare.server_ip', $_SERVER['SERVER_ADDR'] ?? '');
    }

    /**
     * Add a custom domain for a tenant.
     * Creates necessary DNS records and SSL certificate.
     */
    public function addCustomDomain(Tenant $tenant, string $domain): array
    {
        try {
            // Validate domain format
            if (!$this->isValidDomain($domain)) {
                return [
                    'success' => false,
                    'message' => 'Invalid domain format',
                ];
            }

            // Check if domain is not a vumacloud.com subdomain (not allowed)
            if ($this->isVumaCloudSubdomain($domain)) {
                return [
                    'success' => false,
                    'message' => 'Vendor stores cannot use vumacloud.com subdomains. Please use your own custom domain.',
                ];
            }

            // Add domain to Cloudflare (if using Cloudflare for SaaS)
            $result = $this->addDomainToCloudflare($domain);

            if (!$result['success']) {
                return $result;
            }

            // Create DNS records pointing to our server
            $dnsResult = $this->createDnsRecords($domain);

            if (!$dnsResult['success']) {
                return $dnsResult;
            }

            // Enable SSL/TLS
            $sslResult = $this->enableSsl($domain);

            // Update tenant record
            $tenant->update([
                'domain' => $domain,
                'domain_verified' => false,
                'domain_verification_token' => $this->generateVerificationToken(),
                'cloudflare_zone_id' => $result['zone_id'] ?? null,
            ]);

            Log::info('Custom domain added for tenant', [
                'tenant_id' => $tenant->id,
                'domain' => $domain,
            ]);

            return [
                'success' => true,
                'message' => 'Domain added successfully. Please update your domain\'s nameservers.',
                'nameservers' => $result['nameservers'] ?? [],
                'verification_token' => $tenant->domain_verification_token,
                'instructions' => $this->getDnsInstructions($domain),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to add custom domain', [
                'tenant_id' => $tenant->id,
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to add domain: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Add domain to Cloudflare (for Cloudflare for SaaS / SSL for SaaS).
     */
    protected function addDomainToCloudflare(string $domain): array
    {
        // For Cloudflare for SaaS (custom hostnames)
        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}/zones/{$this->zoneId}/custom_hostnames", [
                'hostname' => $domain,
                'ssl' => [
                    'method' => 'http',
                    'type' => 'dv',
                    'settings' => [
                        'http2' => 'on',
                        'min_tls_version' => '1.2',
                        'tls_1_3' => 'on',
                    ],
                ],
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'hostname_id' => $data['result']['id'] ?? null,
                'zone_id' => $this->zoneId,
                'ssl_status' => $data['result']['ssl']['status'] ?? 'pending',
            ];
        }

        // If custom hostname fails, try adding as a new zone
        return $this->addAsNewZone($domain);
    }

    /**
     * Add domain as a new Cloudflare zone.
     */
    protected function addAsNewZone(string $domain): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}/zones", [
                'name' => $domain,
                'account' => ['id' => $this->accountId],
                'jump_start' => true,
                'type' => 'full',
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'zone_id' => $data['result']['id'] ?? null,
                'nameservers' => $data['result']['name_servers'] ?? [],
                'status' => $data['result']['status'] ?? 'pending',
            ];
        }

        $error = $response->json();
        return [
            'success' => false,
            'message' => $error['errors'][0]['message'] ?? 'Failed to add domain to Cloudflare',
        ];
    }

    /**
     * Create DNS A record pointing to our server.
     */
    protected function createDnsRecords(string $domain, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        // Create A record for root domain
        $rootResponse = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}/zones/{$zoneId}/dns_records", [
                'type' => 'A',
                'name' => '@',
                'content' => $this->serverIp,
                'ttl' => 1, // Auto
                'proxied' => true, // Enable Cloudflare proxy for SSL and CDN
            ]);

        // Create A record for www subdomain
        $wwwResponse = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}/zones/{$zoneId}/dns_records", [
                'type' => 'A',
                'name' => 'www',
                'content' => $this->serverIp,
                'ttl' => 1,
                'proxied' => true,
            ]);

        if ($rootResponse->successful() || $wwwResponse->successful()) {
            return [
                'success' => true,
                'message' => 'DNS records created',
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to create DNS records',
        ];
    }

    /**
     * Enable SSL for the domain.
     */
    protected function enableSsl(string $domain, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        // Set SSL mode to Full (Strict)
        $response = Http::withHeaders($this->getHeaders())
            ->patch("{$this->baseUrl}/zones/{$zoneId}/settings/ssl", [
                'value' => 'full',
            ]);

        // Enable Always Use HTTPS
        Http::withHeaders($this->getHeaders())
            ->patch("{$this->baseUrl}/zones/{$zoneId}/settings/always_use_https", [
                'value' => 'on',
            ]);

        // Enable Automatic HTTPS Rewrites
        Http::withHeaders($this->getHeaders())
            ->patch("{$this->baseUrl}/zones/{$zoneId}/settings/automatic_https_rewrites", [
                'value' => 'on',
            ]);

        return [
            'success' => $response->successful(),
            'message' => $response->successful() ? 'SSL enabled' : 'Failed to enable SSL',
        ];
    }

    /**
     * Verify domain ownership.
     */
    public function verifyDomain(Tenant $tenant): array
    {
        $domain = $tenant->domain;

        if (!$domain) {
            return ['success' => false, 'message' => 'No domain configured'];
        }

        try {
            // Check if domain resolves to our server
            $resolvedIp = gethostbyname($domain);

            if ($resolvedIp === $this->serverIp) {
                $tenant->update([
                    'domain_verified' => true,
                    'domain_verified_at' => now(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Domain verified successfully',
                ];
            }

            // Check TXT record for verification token
            $txtRecords = dns_get_record($domain, DNS_TXT);
            $expectedToken = "vumashops-verify={$tenant->domain_verification_token}";

            foreach ($txtRecords as $record) {
                if (isset($record['txt']) && $record['txt'] === $expectedToken) {
                    $tenant->update([
                        'domain_verified' => true,
                        'domain_verified_at' => now(),
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Domain verified via TXT record',
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Domain verification failed. Please check your DNS settings.',
                'expected_ip' => $this->serverIp,
                'resolved_ip' => $resolvedIp,
                'verification_token' => $expectedToken,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Verification error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Remove custom domain.
     */
    public function removeDomain(Tenant $tenant): array
    {
        if (!$tenant->domain) {
            return ['success' => true, 'message' => 'No domain to remove'];
        }

        try {
            // Remove from Cloudflare if we have the zone ID
            if ($tenant->cloudflare_zone_id) {
                Http::withHeaders($this->getHeaders())
                    ->delete("{$this->baseUrl}/zones/{$tenant->cloudflare_zone_id}");
            }

            $tenant->update([
                'domain' => null,
                'domain_verified' => false,
                'domain_verification_token' => null,
                'cloudflare_zone_id' => null,
            ]);

            return [
                'success' => true,
                'message' => 'Domain removed successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to remove domain', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to remove domain',
            ];
        }
    }

    /**
     * Get SSL certificate status for a domain.
     */
    public function getSslStatus(string $domain, ?string $zoneId = null): array
    {
        $zoneId = $zoneId ?? $this->zoneId;

        $response = Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl}/zones/{$zoneId}/ssl/certificate_packs");

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'certificates' => $data['result'] ?? [],
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to get SSL status',
        ];
    }

    /**
     * Check if domain is a vumacloud.com subdomain (not allowed for vendors).
     */
    protected function isVumaCloudSubdomain(string $domain): bool
    {
        $domain = strtolower(trim($domain));
        return str_ends_with($domain, '.vumacloud.com') || $domain === 'vumacloud.com';
    }

    /**
     * Validate domain format.
     */
    protected function isValidDomain(string $domain): bool
    {
        return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $domain);
    }

    /**
     * Generate verification token.
     */
    protected function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Get DNS setup instructions for customers.
     */
    public function getDnsInstructions(string $domain): array
    {
        return [
            'option_1' => [
                'title' => 'Use Cloudflare (Recommended)',
                'steps' => [
                    "1. Sign up for free at cloudflare.com",
                    "2. Add your domain: {$domain}",
                    "3. Update nameservers at your registrar",
                    "4. Add A record: @ → {$this->serverIp}",
                    "5. Add A record: www → {$this->serverIp}",
                    "6. Enable SSL/TLS (Full mode)",
                ],
            ],
            'option_2' => [
                'title' => 'Use Your Registrar DNS',
                'steps' => [
                    "1. Go to your domain registrar's DNS settings",
                    "2. Add A record: @ → {$this->serverIp}",
                    "3. Add A record: www → {$this->serverIp}",
                    "4. Wait for DNS propagation (up to 48 hours)",
                ],
            ],
            'verification' => [
                'title' => 'Domain Verification',
                'steps' => [
                    "Add a TXT record to verify ownership:",
                    "Name: @ (or {$domain})",
                    "Value: vumashops-verify=[your-verification-token]",
                ],
            ],
        ];
    }

    /**
     * Get API headers.
     */
    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Purge Cloudflare cache for a domain.
     */
    public function purgeCache(string $zoneId): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}/zones/{$zoneId}/purge_cache", [
                'purge_everything' => true,
            ]);

        return [
            'success' => $response->successful(),
            'message' => $response->successful() ? 'Cache purged' : 'Failed to purge cache',
        ];
    }

    /**
     * Get all custom hostnames for the zone.
     */
    public function listCustomHostnames(): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl}/zones/{$this->zoneId}/custom_hostnames");

        if ($response->successful()) {
            return [
                'success' => true,
                'hostnames' => $response->json()['result'] ?? [],
            ];
        }

        return [
            'success' => false,
            'hostnames' => [],
        ];
    }
}
