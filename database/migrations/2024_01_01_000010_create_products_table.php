<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('attribute_family_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('simple');
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->longText('description')->nullable();
            $table->text('short_description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('special_price', 12, 2)->nullable();
            $table->timestamp('special_price_from')->nullable();
            $table->timestamp('special_price_to')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->decimal('weight', 12, 3)->nullable();
            $table->decimal('width', 12, 2)->nullable();
            $table->decimal('height', 12, 2)->nullable();
            $table->decimal('depth', 12, 2)->nullable();
            $table->boolean('manage_stock')->default(true);
            $table->integer('quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->boolean('allow_backorders')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->string('status')->default('active');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('url_key')->nullable();
            $table->foreignId('tax_category_id')->nullable();
            $table->json('attributes')->nullable();
            $table->json('additional')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'type', 'status']);
            $table->index(['tenant_id', 'is_featured']);
            $table->index(['tenant_id', 'is_visible', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
