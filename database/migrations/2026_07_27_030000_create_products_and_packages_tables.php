<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('catalog_categories')->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name', 180);
            $table->string('description')->nullable();
            $table->string('product_kind', 40);
            $table->decimal('alcohol_by_volume', 5, 2)->nullable();
            $table->string('vat_treatment', 30);
            $table->string('ice_treatment', 40)->default('none');
            $table->string('ice_code', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'is_active']);
            $table->index(['brand_id', 'is_active']);
            $table->index(['vat_treatment', 'ice_treatment']);
            $table->index(['name', 'is_active']);
        });

        Schema::create('product_packages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('presentation_id')->constrained()->restrictOnDelete();
            $table->string('barcode', 80)->nullable()->unique();
            $table->decimal('price_before_tax', 14, 4);
            $table->unsignedInteger('units_per_package')->default(1);
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'presentation_id']);
            $table->index(['product_id', 'is_base', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_packages');
        Schema::dropIfExists('products');
    }
};
