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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();

            // Pricing
            $table->decimal('price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable(); // Original/strikethrough price
            $table->decimal('cost_price', 12, 2)->nullable(); // For profit calculation

            // Inventory
            $table->integer('stock_quantity')->default(0);
            $table->boolean('track_inventory')->default(true);
            $table->boolean('allow_backorder')->default(false);
            $table->integer('low_stock_threshold')->default(5);

            // Physical properties
            $table->decimal('weight', 10, 3)->nullable(); // kg
            $table->decimal('length', 10, 2)->nullable(); // cm
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();

            // Status
            $table->string('status')->default('draft'); // draft, active, archived
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_digital')->default(false);

            // Media (JSON array of image URLs)
            $table->json('images')->nullable();
            $table->string('featured_image')->nullable();

            // SEO
            $table->json('meta')->nullable();

            // Attributes for variants
            $table->json('attributes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('is_featured');
            $table->index('category_id');
        });

        // Product variants (e.g., Size: Small, Color: Red)
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., "Small / Red"
            $table->string('sku')->nullable();
            $table->decimal('price', 12, 2)->nullable(); // Override product price
            $table->integer('stock_quantity')->default(0);
            $table->json('options')->nullable(); // { "size": "Small", "color": "Red" }
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('product_id');
            $table->unique(['product_id', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};
