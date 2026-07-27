<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->char('ruc', 13)->unique();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('taxpayer_type', 40)->default('natural_person');
            $table->string('rimpe_category', 40)->nullable();
            $table->boolean('accounting_required')->default(false);
            $table->boolean('withholding_agent')->default(false);
            $table->string('withholding_agent_resolution')->nullable();
            $table->boolean('special_taxpayer')->default(false);
            $table->string('special_taxpayer_resolution')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address');
            $table->char('currency', 3)->default('USD');
            $table->string('timezone', 50)->default('America/Guayaquil');
            $table->timestamps();
        });

        Schema::create('establishments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->char('code', 3);
            $table->string('name');
            $table->string('trade_name')->nullable();
            $table->text('address');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_main')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::create('warehouses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('establishment_id')->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_main')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_negative_stock')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['establishment_id', 'is_active']);
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->string('description')->nullable();
            $table->json('permissions');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 30)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['phone', 'is_active', 'last_login_at']);
        });

        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('establishments');
        Schema::dropIfExists('companies');
    }
};
