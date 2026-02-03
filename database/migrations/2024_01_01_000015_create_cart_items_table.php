<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('cart_items')->nullOnDelete();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->integer('quantity');
            $table->decimal('price', 12, 2);
            $table->decimal('weight', 12, 3)->nullable();
            $table->json('options')->nullable();
            $table->json('additional')->nullable();
            $table->timestamps();

            $table->index(['cart_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
