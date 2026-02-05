<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\BagistoProvisioner;
use App\Services\NginxConfigGenerator;
use App\Services\SslManager;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ProvisionTenant extends Command
{
    protected $signature = 'tenant:provision
                            {--name= : Store name}
                            {--email= : Admin email}
                            {--domain= : Custom domain}
                            {--plan= : Plan slug}
                            {--password= : Admin password}
                            {--skip-ssl : Skip SSL certificate issuance}';

    protected $description = 'Manually provision a new Bagisto tenant';

    public function __construct(
        protected BagistoProvisioner $provisioner,
        protected NginxConfigGenerator $nginxGenerator,
        protected SslManager $sslManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Provisioning new Bagisto tenant...');
        $this->newLine();

        // Gather inputs
        $name = $this->option('name') ?? $this->ask('Store name');
        $email = $this->option('email') ?? $this->ask('Admin email');
        $domain = $this->option('domain') ?? $this->ask('Custom domain (e.g., mystore.com)');
        $planSlug = $this->option('plan') ?? $this->choice('Plan', Plan::pluck('name', 'slug')->toArray());
        $password = $this->option('password') ?? $this->secret('Admin password (min 8 characters)');

        // Validate
        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters');
            return Command::FAILURE;
        }

        $plan = Plan::where('slug', $planSlug)->first();
        if (!$plan) {
            $this->error("Plan not found: {$planSlug}");
            return Command::FAILURE;
        }

        // Check if domain already exists
        if (Tenant::whereHas('domains', fn($q) => $q->where('domain', $domain))->exists()) {
            $this->error("Domain already in use: {$domain}");
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Creating tenant record...');

        // Create tenant
        $tenant = Tenant::create([
            'id' => Str::uuid()->toString(),
            'name' => $name,
            'email' => $email,
            'country' => 'KE',
            'currency' => 'KES',
            'timezone' => 'Africa/Nairobi',
            'locale' => 'en',
            'plan_id' => $plan->id,
            'subscription_status' => 'active',
            'is_active' => true,
            'ssl_status' => 'pending',
        ]);

        // Add domain
        $tenant->domains()->create(['domain' => $domain]);

        $this->info("Tenant created: {$tenant->id}");

        // Generate nginx config (HTTP only initially)
        $this->info('Generating Nginx configuration...');
        $this->nginxGenerator->generate($tenant);

        // Provision Bagisto
        $this->info('Installing Bagisto (this may take several minutes)...');
        $this->newLine();

        try {
            $this->provisioner->provision($tenant, [
                'admin_email' => $email,
                'admin_password' => $password,
                'storefront_type' => 'bagisto_default',
            ]);
        } catch (\Exception $e) {
            $this->error('Bagisto installation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Bagisto installed successfully!');

        // Issue SSL certificate
        if (!$this->option('skip-ssl')) {
            $this->newLine();
            $this->info('Issuing SSL certificate...');

            if ($this->sslManager->verifyDns($domain)) {
                if ($this->sslManager->issueCertificate($tenant)) {
                    // Regenerate nginx config with SSL
                    $this->nginxGenerator->generate($tenant);
                    $this->info('SSL certificate issued!');
                } else {
                    $this->warn('SSL certificate issuance failed. You can retry later.');
                }
            } else {
                $this->warn("DNS not pointing to server yet. Add an A record for {$domain} pointing to " . config('services.server.ip'));
                $this->warn('Run: php artisan tenant:ssl ' . $tenant->id);
            }
        }

        $this->newLine();
        $this->info('Tenant provisioned successfully!');
        $this->newLine();

        $this->table(['Property', 'Value'], [
            ['Tenant ID', $tenant->id],
            ['Store Name', $tenant->name],
            ['Domain', $domain],
            ['Admin Email', $email],
            ['Plan', $plan->name],
            ['Bagisto Admin', $tenant->getAdminUrl()],
            ['GraphQL API', $tenant->getApiUrl()],
            ['Storefront', $tenant->getStorefrontUrl()],
            ['SSL Status', $tenant->ssl_status],
        ]);

        return Command::SUCCESS;
    }
}
