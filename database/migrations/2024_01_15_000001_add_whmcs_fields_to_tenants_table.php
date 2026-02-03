<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('whmcs_service_id')->nullable()->after('metadata');
            $table->string('whmcs_client_id')->nullable()->after('whmcs_service_id');
            $table->timestamp('suspended_at')->nullable()->after('is_active');
            $table->string('suspension_reason')->nullable()->after('suspended_at');

            $table->index('whmcs_service_id');
            $table->index('whmcs_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['whmcs_service_id']);
            $table->dropIndex(['whmcs_client_id']);
            $table->dropColumn(['whmcs_service_id', 'whmcs_client_id', 'suspended_at', 'suspension_reason']);
        });
    }
};
