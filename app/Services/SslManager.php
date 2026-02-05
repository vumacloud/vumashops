<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Manages SSL certificates for tenant domains using Let's Encrypt
 */
class SslManager
{
    protected string $certbotEmail;
    protected string $webroot = '/var/www/certbot';

    public function __construct()
    {
        $this->certbotEmail = config('services.letsencrypt.email', 'admin@vumacloud.com');
    }

    /**
     * Issue SSL certificate for a tenant's domain
     */
    public function issueCertificate(Tenant $tenant): bool
    {
        $domain = $tenant->getPrimaryDomain();

        if (!$domain) {
            Log::error("SSL: No domain found for tenant {$tenant->id}");
            return false;
        }

        try {
            // Update status to verifying
            $tenant->update(['ssl_status' => 'verifying']);

            // Verify DNS is pointing to our server
            if (!$this->verifyDns($domain)) {
                $tenant->update(['ssl_status' => 'failed']);
                Log::error("SSL: DNS verification failed for {$domain}");
                return false;
            }

            // Update status to issuing
            $tenant->update(['ssl_status' => 'issuing']);

            // Request certificate from Let's Encrypt
            $result = Process::timeout(120)->run([
                'certbot', 'certonly',
                '--webroot',
                '-w', $this->webroot,
                '-d', $domain,
                '--email', $this->certbotEmail,
                '--agree-tos',
                '--non-interactive',
                '--expand',
            ]);

            if (!$result->successful()) {
                Log::error("SSL: Certbot failed for {$domain}", [
                    'output' => $result->output(),
                    'error' => $result->errorOutput(),
                ]);
                $tenant->update(['ssl_status' => 'failed']);
                return false;
            }

            // Update tenant with SSL info
            $tenant->update([
                'ssl_status' => 'active',
                'ssl_issued_at' => now(),
                'ssl_expires_at' => now()->addDays(90), // Let's Encrypt certs are valid for 90 days
            ]);

            // Reload nginx to pick up new certificate
            $this->reloadNginx();

            Log::info("SSL: Certificate issued for {$domain}");

            return true;

        } catch (\Exception $e) {
            Log::error("SSL: Exception for {$domain}", ['error' => $e->getMessage()]);
            $tenant->update(['ssl_status' => 'failed']);
            return false;
        }
    }

    /**
     * Renew SSL certificate for a tenant
     */
    public function renewCertificate(Tenant $tenant): bool
    {
        $domain = $tenant->getPrimaryDomain();

        if (!$domain) {
            return false;
        }

        try {
            $result = Process::timeout(120)->run([
                'certbot', 'renew',
                '--cert-name', $domain,
                '--non-interactive',
            ]);

            if ($result->successful()) {
                $tenant->update([
                    'ssl_expires_at' => now()->addDays(90),
                ]);
                $this->reloadNginx();
                Log::info("SSL: Certificate renewed for {$domain}");
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error("SSL: Renewal failed for {$domain}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Revoke and delete SSL certificate
     */
    public function revokeCertificate(Tenant $tenant): bool
    {
        $domain = $tenant->getPrimaryDomain();

        if (!$domain) {
            return true;
        }

        try {
            Process::timeout(60)->run([
                'certbot', 'delete',
                '--cert-name', $domain,
                '--non-interactive',
            ]);

            $tenant->update([
                'ssl_status' => 'pending',
                'ssl_issued_at' => null,
                'ssl_expires_at' => null,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("SSL: Revocation failed for {$domain}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Verify DNS is pointing to our server
     */
    public function verifyDns(string $domain): bool
    {
        $serverIp = config('services.server.ip', '164.92.184.13');

        // Get A record for the domain
        $records = dns_get_record($domain, DNS_A);

        if (empty($records)) {
            Log::warning("SSL: No A record found for {$domain}");
            return false;
        }

        foreach ($records as $record) {
            if (isset($record['ip']) && $record['ip'] === $serverIp) {
                return true;
            }
        }

        Log::warning("SSL: DNS not pointing to {$serverIp} for {$domain}", [
            'records' => $records,
        ]);

        return false;
    }

    /**
     * Check if certificate exists for domain
     */
    public function certificateExists(string $domain): bool
    {
        $certPath = "/etc/letsencrypt/live/{$domain}/fullchain.pem";
        return file_exists($certPath);
    }

    /**
     * Get certificate expiry date
     */
    public function getCertificateExpiry(string $domain): ?\DateTime
    {
        $certPath = "/etc/letsencrypt/live/{$domain}/fullchain.pem";

        if (!file_exists($certPath)) {
            return null;
        }

        $certData = openssl_x509_parse(file_get_contents($certPath));

        if ($certData && isset($certData['validTo_time_t'])) {
            return new \DateTime('@' . $certData['validTo_time_t']);
        }

        return null;
    }

    /**
     * Reload nginx configuration
     */
    protected function reloadNginx(): void
    {
        Process::run(['nginx', '-t']);
        Process::run(['systemctl', 'reload', 'nginx']);
    }

    /**
     * Renew all certificates expiring within 30 days
     */
    public function renewExpiringCertificates(): int
    {
        $renewed = 0;

        $tenants = Tenant::where('ssl_status', 'active')
            ->where('ssl_expires_at', '<=', now()->addDays(30))
            ->get();

        foreach ($tenants as $tenant) {
            if ($this->renewCertificate($tenant)) {
                $renewed++;
            }
        }

        return $renewed;
    }
}
