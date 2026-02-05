<?php

namespace App\Filament\Admin\Resources\TenantResource\Pages;

use App\Filament\Admin\Resources\TenantResource;
use App\Services\BagistoProvisioner;
use App\Services\NginxConfigGenerator;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate UUID for the tenant
        $data['id'] = Str::uuid()->toString();

        // Set defaults
        $data['ssl_status'] = 'pending';
        $data['locale'] = $data['locale'] ?? 'en';

        // Store domain and password temporarily (not DB columns)
        $this->domain = $data['domain'] ?? null;
        $this->adminPassword = $data['admin_password'] ?? null;

        // Remove non-DB fields
        unset($data['domain'], $data['admin_password']);

        return $data;
    }

    protected ?string $domain = null;
    protected ?string $adminPassword = null;

    protected function afterCreate(): void
    {
        $tenant = $this->record;

        // Create domain record
        if ($this->domain) {
            $tenant->domains()->create([
                'domain' => strtolower(trim($this->domain)),
            ]);
        }

        // Generate nginx config
        try {
            $nginxGenerator = app(NginxConfigGenerator::class);
            $nginxGenerator->generate($tenant);
        } catch (\Exception $e) {
            // Log but don't fail - nginx config can be regenerated later
            \Log::warning("Failed to generate nginx config for tenant {$tenant->id}: " . $e->getMessage());
        }

        // Queue Bagisto provisioning (runs in background)
        if ($this->adminPassword && $this->domain) {
            try {
                $provisioner = app(BagistoProvisioner::class);
                $provisioner->provision($tenant, [
                    'admin_email' => $tenant->email,
                    'admin_password' => $this->adminPassword,
                    'storefront_type' => 'bagisto_default',
                ]);

                Notification::make()
                    ->title('Bagisto Installation Started')
                    ->body('Bagisto is being installed for ' . $tenant->name . '. This may take several minutes.')
                    ->success()
                    ->send();

            } catch (\Exception $e) {
                Notification::make()
                    ->title('Bagisto Provisioning Failed')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                \Log::error("Failed to provision Bagisto for tenant {$tenant->id}: " . $e->getMessage());
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
