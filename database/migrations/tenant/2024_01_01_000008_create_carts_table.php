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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable()->index();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency', 3)->default('KES');
            $table->string('coupon_code')->nullable();
            $table->timestamp('abandoned_at')->nullable();
            $table->boolean('is_abandoned_email_sent')->default(false);
            $table->timestamps();

            $table->index('abandoned_at');
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->json('options')->nullable();
            $table->timestamps();

            $table->unique(['cart_id', 'product_id', 'variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
