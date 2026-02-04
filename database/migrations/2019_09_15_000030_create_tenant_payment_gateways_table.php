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
        Schema::create('tenant_payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            // Gateway identifier: paystack, flutterwave, mpesa, mtn_momo, airtel_money, stripe, paypal
            $table->string('gateway');
            $table->string('display_name')->nullable();

            // Encrypted credentials (JSON)
            // e.g., { "public_key": "...", "secret_key": "...", "webhook_secret": "..." }
            $table->text('credentials')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_test_mode')->default(true);

            // Supported currencies for this gateway config
            $table->json('supported_currencies')->nullable();

            // Additional settings
            $table->json('settings')->nullable();

            $table->timestamps();

            // Unique constraint: one gateway per tenant
            $table->unique(['tenant_id', 'gateway']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_payment_gateways');
    }
};
