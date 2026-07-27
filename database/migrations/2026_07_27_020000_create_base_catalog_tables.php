<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('catalog_categories')->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 120);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'is_active']);
            $table->index(['name', 'is_active']);
        });

        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 120)->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['name', 'is_active']);
        });

        Schema::create('measurement_units', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 12)->unique();
            $table->string('name', 80)->unique();
            $table->string('symbol', 12);
            $table->string('dimension', 20);
            $table->boolean('allows_decimals')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['dimension', 'is_active']);
        });

        Schema::create('presentations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('measurement_unit_id')->constrained()->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 120);
            $table->decimal('quantity', 14, 4);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['measurement_unit_id', 'is_active']);
            $table->index(['name', 'is_active']);
        });

        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->char('sri_code', 2);
            $table->string('name', 150);
            $table->boolean('requires_reference')->default(false);
            $table->boolean('affects_cash')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['sri_code', 'is_active']);
            $table->index(['name', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('presentations');
        Schema::dropIfExists('measurement_units');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('catalog_categories');
    }
};
