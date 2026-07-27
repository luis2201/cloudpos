<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('tax_type', 40);
            $table->string('name', 100);
            $table->decimal('rate', 5, 2);
            $table->date('effective_from');
            $table->string('legal_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tax_type', 'effective_from']);
            $table->index(['tax_type', 'effective_from'], 'tax_rates_effective_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
