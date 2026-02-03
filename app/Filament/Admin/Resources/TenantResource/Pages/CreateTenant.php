<?php

namespace App\Filament\Admin\Resources\TenantResource\Pages;

use App\Filament\Admin\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set trial end date if not set
        if (empty($data['trial_ends_at']) && $data['subscription_status'] === 'trial') {
            $plan = \App\Models\Plan::find($data['plan_id']);
            $trialDays = $plan?->trial_days ?? 14;
            $data['trial_ends_at'] = now()->addDays($trialDays);
        }

        return $data;
    }
}
