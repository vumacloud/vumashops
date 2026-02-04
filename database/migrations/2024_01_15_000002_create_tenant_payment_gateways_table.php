<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('gateway'); // paystack, flutterwave, mpesa_kenya, mpesa_tanzania, mtn_momo, airtel_money
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_test_mode')->default(true);
            $table->json('credentials')->nullable(); // Encrypted JSON with API keys
            $table->json('settings')->nullable(); // Gateway-specific settings
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'gateway']);
            $table->index(['tenant_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payment_gateways');
    }
};
