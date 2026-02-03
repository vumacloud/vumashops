<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type'); // text, textarea, select, multiselect, boolean, date, datetime, price, file, image
            $table->text('description')->nullable();
            $table->string('validation_rules')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_comparable')->default(false);
            $table->boolean('is_visible_on_front')->default(true);
            $table->boolean('use_in_flat')->default(true);
            $table->boolean('is_configurable')->default(false);
            $table->integer('position')->default(0);
            $table->json('options')->nullable();
            $table->json('additional')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_filterable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
