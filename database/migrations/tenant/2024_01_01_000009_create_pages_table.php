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
        // Static pages (About, Contact, Terms, Privacy, etc.)
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->boolean('is_published')->default(true);
            $table->json('meta')->nullable(); // SEO meta
            $table->timestamps();

            $table->index('is_published');
        });

        // Navigation menus
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location'); // header, footer, sidebar
            $table->json('items')->nullable(); // Menu items as JSON
            $table->timestamps();

            $table->unique('location');
        });

        // Store announcements/banners
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->string('type')->default('info'); // info, warning, success, promo
            $table->string('link')->nullable();
            $table->string('link_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('pages');
    }
};
