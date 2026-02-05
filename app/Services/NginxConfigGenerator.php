<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Generates and manages Nginx configurations for tenant domains
 */
class NginxConfigGenerator
{
    protected string $configPath = '/etc/nginx/sites-available';
    protected string $enabledPath = '/etc/nginx/sites-enabled';
    protected string $certbotWebroot = '/var/www/certbot';

    /**
     * Generate Nginx config for a tenant
     */
    public function generate(Tenant $tenant): bool
    {
        $domain = $tenant->getPrimaryDomain();

        if (!$domain) {
            Log::error("Nginx: No domain for tenant {$tenant->id}");
            return false;
        }

        $bagistoPath = $tenant->getBagistoPath();
        $hasSsl = $tenant->ssl_status === 'active';

        $config = $this->buildConfig($domain, $bagistoPath, $hasSsl);

        // Write config file
        $configFile = "{$this->configPath}/{$domain}.conf";
        file_put_contents($configFile, $config);

        // Enable the site
        $enabledLink = "{$this->enabledPath}/{$domain}.conf";
        if (!file_exists($enabledLink)) {
            symlink($configFile, $enabledLink);
        }

        // Test and reload nginx
        return $this->reloadNginx();
    }

    /**
     * Build the nginx configuration
     */
    protected function buildConfig(string $domain, string $bagistoPath, bool $hasSsl): string
    {
        $publicPath = "{$bagistoPath}/public";

        if ($hasSsl) {
            return $this->buildSslConfig($domain, $publicPath);
        }

        return $this->buildHttpConfig($domain, $publicPath);
    }

    /**
     * Build HTTP-only config (before SSL is issued)
     */
    protected function buildHttpConfig(string $domain, string $publicPath): string
    {
        return <<<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name {$domain};

    root {$publicPath};
    index index.php index.html;

    # Let's Encrypt challenge
    location /.well-known/acme-challenge/ {
        root {$this->certbotWebroot};
    }

    # Laravel/Bagisto
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
}
NGINX;
    }

    /**
     * Build HTTPS config (after SSL is issued)
     */
    protected function buildSslConfig(string $domain, string $publicPath): string
    {
        return <<<NGINX
# HTTP -> HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name {$domain};

    # Let's Encrypt challenge
    location /.well-known/acme-challenge/ {
        root {$this->certbotWebroot};
    }

    location / {
        return 301 https://\$host\$request_uri;
    }
}

# HTTPS server
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name {$domain};

    root {$publicPath};
    index index.php index.html;

    # SSL certificates
    ssl_certificate /etc/letsencrypt/live/{$domain}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/{$domain}/privkey.pem;

    # SSL settings
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;
    ssl_session_tickets off;

    # HSTS
    add_header Strict-Transport-Security "max-age=63072000" always;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Laravel/Bagisto
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    # Upload limits for e-commerce
    client_max_body_size 100M;
}
NGINX;
    }

    /**
     * Remove nginx config for a tenant
     */
    public function remove(Tenant $tenant): bool
    {
        $domain = $tenant->getPrimaryDomain();

        if (!$domain) {
            return true;
        }

        $configFile = "{$this->configPath}/{$domain}.conf";
        $enabledLink = "{$this->enabledPath}/{$domain}.conf";

        if (file_exists($enabledLink)) {
            unlink($enabledLink);
        }

        if (file_exists($configFile)) {
            unlink($configFile);
        }

        return $this->reloadNginx();
    }

    /**
     * Test and reload nginx
     */
    protected function reloadNginx(): bool
    {
        $test = Process::run(['nginx', '-t']);

        if (!$test->successful()) {
            Log::error("Nginx: Config test failed", [
                'output' => $test->output(),
                'error' => $test->errorOutput(),
            ]);
            return false;
        }

        Process::run(['systemctl', 'reload', 'nginx']);

        return true;
    }

    /**
     * Generate config for the central platform
     */
    public function generateCentralConfig(): string
    {
        $centralDomain = 'shops.vumacloud.com';
        $centralPath = '/var/www/vumashops/public';

        return <<<NGINX
# VumaShops Central Platform
server {
    listen 80;
    listen [::]:80;
    server_name {$centralDomain};

    location /.well-known/acme-challenge/ {
        root {$this->certbotWebroot};
    }

    location / {
        return 301 https://\$host\$request_uri;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name {$centralDomain};

    root {$centralPath};
    index index.php index.html;

    ssl_certificate /etc/letsencrypt/live/{$centralDomain}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/{$centralDomain}/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;

    add_header Strict-Transport-Security "max-age=63072000" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    client_max_body_size 50M;
}
NGINX;
    }
}
