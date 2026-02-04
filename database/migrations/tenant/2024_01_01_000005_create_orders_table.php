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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

            // Status tracking
            $table->string('status')->default('pending'); // pending, processing, shipped, delivered, cancelled, refunded
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->string('fulfillment_status')->default('unfulfilled'); // unfulfilled, partial, fulfilled

            // Pricing
            $table->string('currency', 3)->default('KES');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Payment
            $table->string('payment_method')->nullable(); // paystack, mpesa, etc.
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Customer info (denormalized for historical record)
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();

            // Addresses (JSON for historical record)
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();

            // Shipping
            $table->string('shipping_method')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // Discounts
            $table->string('coupon_code')->nullable();
            $table->foreignId('coupon_id')->nullable();

            // Notes
            $table->text('customer_notes')->nullable();
            $table->text('staff_notes')->nullable();

            // Meta
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('payment_status');
            $table->index('customer_id');
            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable();
            $table->string('name'); // Product name (denormalized)
            $table->string('sku')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->json('options')->nullable(); // Variant options at time of purchase
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });

        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable(); // Staff who added note
            $table->text('note');
            $table->boolean('is_customer_visible')->default(false);
            $table->timestamps();

            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_notes');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
