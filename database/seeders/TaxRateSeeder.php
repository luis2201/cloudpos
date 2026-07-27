<?php

namespace Database\Seeders;

use App\Domain\Tax\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        TaxRate::query()->updateOrCreate(
            [
                'tax_type' => TaxRate::IVA_GENERAL,
                'effective_from' => '2024-04-01',
            ],
            [
                'name' => 'IVA general',
                'rate' => 15.00,
                'legal_reference' => 'Decreto Ejecutivo No. 198',
                'notes' => 'Tarifa inicial del proyecto. Debe validarse con la normativa vigente antes de producción.',
            ],
        );
    }
}
