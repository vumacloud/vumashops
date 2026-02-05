<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Handles Bagisto installation and provisioning for tenants
 *
 * Each tenant gets:
 * - Dedicated MySQL database
 * - Fresh Bagisto installation
 * - GraphQL API (headless-ecommerce package)
 * - Optional Next.js storefront
 */
class BagistoProvisioner
{
    protected string $bagistoVersion = '2.3.0';
    protected string $tenantsBasePath = '/var/www/tenants';

    /**
     * Provision a new Bagisto installation for a tenant
     */
    public function provision(Tenant $tenant, array $options = []): bool
    {
        $adminEmail = $options['admin_email'] ?? $tenant->email;
        $adminPassword = $options['admin_password'] ?? Str::random(16);

        try {
            Log::info("Provisioning Bagisto for tenant: {$tenant->id}");

            // Step 1: Create database
            $database = $this->createDatabase($tenant);

            // Step 2: Install Bagisto
            $path = $this->installBagisto($tenant, $database, $adminEmail, $adminPassword);

            // Step 3: Install headless-ecommerce (GraphQL API)
            $this->installHeadlessApi($path);

            // Step 4: Configure for production
            $this->configureForProduction($tenant, $path);

            // Step 5: Update tenant record
            $tenant->update([
                'bagisto_path' => $path,
                'bagisto_database' => $database,
                'bagisto_version' => $this->bagistoVersion,
                'bagisto_installed_at' => now(),
                'storefront_type' => $options['storefront_type'] ?? 'bagisto_default',
            ]);

            // Step 6: Store admin credentials securely (for WHMCS to retrieve)
            $tenant->setSetting('admin_email', $adminEmail);
            $tenant->setSetting('admin_password_hash', bcrypt($adminPassword));

            Log::info("Bagisto provisioned successfully for tenant: {$tenant->id}");

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to provision Bagisto for tenant: {$tenant->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Cleanup on failure
            $this->cleanup($tenant);

            throw $e;
        }
    }

    /**
     * Create dedicated MySQL database for tenant's Bagisto
     */
    protected function createDatabase(Tenant $tenant): string
    {
        $database = 'bagisto_' . Str::slug($tenant->id, '_');

        // Create database
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        Log::info("Created database: {$database}");

        return $database;
    }

    /**
     * Install Bagisto via Composer
     */
    protected function installBagisto(Tenant $tenant, string $database, string $adminEmail, string $adminPassword): string
    {
        $path = "{$this->tenantsBasePath}/{$tenant->id}";

        // Create directory
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        // Install Bagisto via Composer
        $result = Process::timeout(600)->path($this->tenantsBasePath)->run([
            'composer', 'create-project', 'bagisto/bagisto', $tenant->id,
            '--no-interaction', '--prefer-dist',
        ]);

        if (!$result->successful()) {
            throw new \Exception("Composer install failed: " . $result->errorOutput());
        }

        // Configure .env
        $this->configureEnv($path, $tenant, $database, $adminEmail, $adminPassword);

        // Run Bagisto installer
        $result = Process::timeout(300)->path($path)->run([
            'php', 'artisan', 'bagisto:install', '--skip-env-check', '--skip-admin-creation',
        ]);

        if (!$result->successful()) {
            // Try alternative installation
            Process::timeout(300)->path($path)->run(['php', 'artisan', 'migrate', '--force']);
            Process::timeout(60)->path($path)->run(['php', 'artisan', 'db:seed', '--force']);
        }

        // Create admin user
        Process::timeout(60)->path($path)->run([
            'php', 'artisan', 'tinker', '--execute',
            "\\Webkul\\User\\Models\\Admin::create(['name' => 'Admin', 'email' => '{$adminEmail}', 'password' => bcrypt('{$adminPassword}'), 'role_id' => 1, 'status' => 1]);",
        ]);

        Log::info("Bagisto installed at: {$path}");

        return $path;
    }

    /**
     * Install headless-ecommerce (GraphQL API) package
     */
    protected function installHeadlessApi(string $path): void
    {
        $result = Process::timeout(300)->path($path)->run([
            'composer', 'require', 'bagisto/headless-ecommerce', '--no-interaction',
        ]);

        if (!$result->successful()) {
            Log::warning("Failed to install headless-ecommerce: " . $result->errorOutput());
            return;
        }

        // Publish and setup
        Process::timeout(60)->path($path)->run([
            'php', 'artisan', 'bagisto-graphql:install', '--force',
        ]);

        Log::info("Headless API installed at: {$path}");
    }

    /**
     * Configure .env file for the Bagisto installation
     */
    protected function configureEnv(string $path, Tenant $tenant, string $database, string $adminEmail, string $adminPassword): void
    {
        $envPath = "{$path}/.env";
        $domain = $tenant->getPrimaryDomain() ?? 'localhost';

        $envContent = <<<ENV
APP_NAME="{$tenant->name}"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://{$domain}
APP_TIMEZONE={$tenant->timezone}
APP_LOCALE={$tenant->locale}
APP_CURRENCY={$tenant->currency}

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST={$this->getDbHost()}
DB_PORT={$this->getDbPort()}
DB_DATABASE={$database}
DB_USERNAME={$this->getDbUsername()}
DB_PASSWORD={$this->getDbPassword()}

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST={$this->getRedisHost()}
REDIS_PASSWORD={$this->getRedisPassword()}
REDIS_PORT={$this->getRedisPort()}
REDIS_PREFIX=bagisto_{$tenant->id}_

MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@{$domain}
MAIL_FROM_NAME="{$tenant->name}"
ENV;

        file_put_contents($envPath, $envContent);

        // Generate app key
        Process::timeout(30)->path($path)->run(['php', 'artisan', 'key:generate', '--force']);
    }

    /**
     * Configure Bagisto for production
     */
    protected function configureForProduction(Tenant $tenant, string $path): void
    {
        // Optimize
        Process::timeout(60)->path($path)->run(['php', 'artisan', 'optimize']);
        Process::timeout(60)->path($path)->run(['php', 'artisan', 'view:cache']);
        Process::timeout(60)->path($path)->run(['php', 'artisan', 'route:cache']);

        // Set permissions
        Process::run(['chmod', '-R', '775', "{$path}/storage"]);
        Process::run(['chmod', '-R', '775', "{$path}/bootstrap/cache"]);
        Process::run(['chown', '-R', 'www-data:www-data', $path]);

        // Create storage link
        Process::timeout(30)->path($path)->run(['php', 'artisan', 'storage:link']);
    }

    /**
     * Suspend a tenant's Bagisto installation
     */
    public function suspend(Tenant $tenant): bool
    {
        $path = $tenant->getBagistoPath();

        if (!is_dir($path)) {
            return true;
        }

        // Put into maintenance mode
        Process::timeout(30)->path($path)->run(['php', 'artisan', 'down']);

        Log::info("Bagisto suspended for tenant: {$tenant->id}");

        return true;
    }

    /**
     * Unsuspend a tenant's Bagisto installation
     */
    public function unsuspend(Tenant $tenant): bool
    {
        $path = $tenant->getBagistoPath();

        if (!is_dir($path)) {
            return false;
        }

        // Bring out of maintenance mode
        Process::timeout(30)->path($path)->run(['php', 'artisan', 'up']);

        Log::info("Bagisto unsuspended for tenant: {$tenant->id}");

        return true;
    }

    /**
     * Terminate/delete a tenant's Bagisto installation
     */
    public function terminate(Tenant $tenant): bool
    {
        try {
            // Drop database
            if ($tenant->bagisto_database) {
                DB::statement("DROP DATABASE IF EXISTS `{$tenant->bagisto_database}`");
            }

            // Delete files
            $path = $tenant->getBagistoPath();
            if (is_dir($path)) {
                Process::run(['rm', '-rf', $path]);
            }

            Log::info("Bagisto terminated for tenant: {$tenant->id}");

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to terminate Bagisto for tenant: {$tenant->id}", [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Cleanup after failed provisioning
     */
    protected function cleanup(Tenant $tenant): void
    {
        try {
            $this->terminate($tenant);
        } catch (\Exception $e) {
            Log::error("Cleanup failed for tenant: {$tenant->id}");
        }
    }

    /**
     * Upgrade Bagisto for a tenant
     */
    public function upgrade(Tenant $tenant, string $version = null): bool
    {
        $path = $tenant->getBagistoPath();
        $version = $version ?? $this->bagistoVersion;

        if (!is_dir($path)) {
            return false;
        }

        // Put into maintenance mode
        Process::timeout(30)->path($path)->run(['php', 'artisan', 'down']);

        try {
            // Backup database
            $this->backupDatabase($tenant);

            // Update Bagisto
            Process::timeout(600)->path($path)->run([
                'composer', 'update', '--no-interaction',
            ]);

            // Run migrations
            Process::timeout(300)->path($path)->run([
                'php', 'artisan', 'migrate', '--force',
            ]);

            // Clear caches
            Process::timeout(60)->path($path)->run(['php', 'artisan', 'optimize:clear']);
            Process::timeout(60)->path($path)->run(['php', 'artisan', 'optimize']);

            // Update version
            $tenant->update(['bagisto_version' => $version]);

            Log::info("Bagisto upgraded for tenant: {$tenant->id} to version {$version}");

        } finally {
            // Bring out of maintenance mode
            Process::timeout(30)->path($path)->run(['php', 'artisan', 'up']);
        }

        return true;
    }

    /**
     * Backup tenant's database
     */
    public function backupDatabase(Tenant $tenant): string
    {
        $database = $tenant->bagisto_database;
        $backupPath = storage_path("backups/{$tenant->id}");

        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $filename = "{$backupPath}/{$database}_" . date('Y-m-d_His') . '.sql.gz';

        $command = sprintf(
            'mysqldump -h %s -P %s -u %s -p%s %s | gzip > %s',
            $this->getDbHost(),
            $this->getDbPort(),
            $this->getDbUsername(),
            $this->getDbPassword(),
            $database,
            $filename
        );

        Process::timeout(600)->run($command);

        Log::info("Database backup created: {$filename}");

        return $filename;
    }

    // Helper methods to get DB credentials from central config
    protected function getDbHost(): string
    {
        return config('database.connections.central.host');
    }

    protected function getDbPort(): string
    {
        return config('database.connections.central.port');
    }

    protected function getDbUsername(): string
    {
        return config('database.connections.central.username');
    }

    protected function getDbPassword(): string
    {
        return config('database.connections.central.password');
    }

    protected function getRedisHost(): string
    {
        return config('database.redis.default.host');
    }

    protected function getRedisPassword(): string
    {
        return config('database.redis.default.password') ?? '';
    }

    protected function getRedisPort(): string
    {
        return config('database.redis.default.port');
    }
}
