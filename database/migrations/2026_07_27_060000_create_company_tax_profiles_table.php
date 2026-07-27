<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_tax_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('tax_regime', 50);
            $table->string('taxpayer_type', 40);
            $table->boolean('accounting_required')->default(false);
            $table->boolean('is_franchise')->default(false);
            $table->date('effective_from');
            $table->string('legal_reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'effective_from']);
            $table->index(['company_id', 'tax_regime', 'effective_from']);
        });

        $effectiveFrom = now('America/Guayaquil')->startOfYear()->toDateString();

        foreach (DB::table('companies')->orderBy('id')->get() as $company) {
            $taxRegime = match ($company->rimpe_category) {
                'popular_business' => 'rimpe_popular_business',
                'entrepreneur' => 'rimpe_entrepreneur',
                default => 'general',
            };

            DB::table('company_tax_profiles')->insert([
                'company_id' => $company->id,
                'tax_regime' => $taxRegime,
                'taxpayer_type' => $company->taxpayer_type,
                'accounting_required' => $company->accounting_required,
                'is_franchise' => $company->is_franchise,
                'effective_from' => $effectiveFrom,
                'notes' => 'Perfil inicial migrado desde la configuración principal.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_tax_profiles');
    }
};
