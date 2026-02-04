<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            // UUID primary key (from stancl/tenancy)
            $table->string('id')->primary();

            // Store info
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();

            // Localization
            $table->string('country', 2)->default('KE'); // ISO country code
            $table->string('currency', 3)->default('KES'); // ISO currency code
            $table->string('timezone')->default('Africa/Nairobi');
            $table->string('locale', 10)->default('en');

            // Subscription
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subscription_status')->default('trial'); // trial, active, cancelled, suspended, expired
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('is_active')->default(true);

            // WHMCS Integration
            $table->unsignedBigInteger('whmcs_service_id')->nullable()->index();
            $table->unsignedBigInteger('whmcs_client_id')->nullable()->index();

            // Suspension
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();

            // Bagisto Installation
            $table->string('bagisto_path')->nullable();
            $table->string('bagisto_database')->nullable();
            $table->string('bagisto_version')->nullable();
            $table->timestamp('bagisto_installed_at')->nullable();

            // Storefront
            $table->string('storefront_type')->default('bagisto_default'); // bagisto_default, nextjs, nuxt

            // SSL/Domain
            $table->string('ssl_status')->default('pending'); // pending, verifying, issuing, active, failed
            $table->timestamp('ssl_issued_at')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();

            // Flexible settings and data storage
            $table->json('settings')->nullable();
            $table->json('data')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('subscription_status');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
