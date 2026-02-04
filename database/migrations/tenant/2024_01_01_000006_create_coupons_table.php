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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->text('description')->nullable();

            // Discount type and value
            $table->string('type')->default('percentage'); // percentage, fixed, free_shipping
            $table->decimal('value', 12, 2); // Percentage or fixed amount

            // Minimum requirements
            $table->decimal('minimum_order_amount', 12, 2)->nullable();
            $table->integer('minimum_quantity')->nullable();

            // Maximum discount (for percentage coupons)
            $table->decimal('maximum_discount', 12, 2)->nullable();

            // Usage limits
            $table->integer('usage_limit')->nullable(); // Total usage limit
            $table->integer('usage_limit_per_customer')->nullable();
            $table->integer('times_used')->default(0);

            // Validity
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);

            // Restrictions
            $table->json('applicable_products')->nullable(); // Product IDs
            $table->json('applicable_categories')->nullable(); // Category IDs
            $table->json('excluded_products')->nullable();

            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
            $table->index(['starts_at', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
