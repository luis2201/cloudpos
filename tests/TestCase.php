<?php

namespace Tests;

use App\Domain\Access\Models\Role;
use App\Domain\Organization\Models\Company;
use App\Domain\Organization\Models\Establishment;
use App\Domain\Organization\Models\Warehouse;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function signInConfiguredAdministrator(): User
    {
        $this->seed(RoleSeeder::class);

        $administrator = User::query()->create([
            'name' => 'Administrador CloudPOS',
            'email' => 'admin@cloudpos.test',
            'password' => 'SecurePass123',
            'is_active' => true,
        ]);
        $administrator->roles()->attach(Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail());

        $company = Company::query()->create([
            'ruc' => '1712345678001',
            'legal_name' => 'María Propietaria',
            'trade_name' => 'Licorería Central',
            'taxpayer_type' => 'natural_person',
            'rimpe_category' => 'popular_business',
            'ice_taxpayer_role' => 'retailer',
            'is_franchise' => false,
            'accounting_required' => false,
            'address' => 'Quito, Ecuador',
            'currency' => 'USD',
            'timezone' => 'America/Guayaquil',
        ]);
        $company->taxProfiles()->create([
            'tax_regime' => 'rimpe_popular_business',
            'taxpayer_type' => 'natural_person',
            'accounting_required' => false,
            'is_franchise' => false,
            'effective_from' => now('America/Guayaquil')->startOfYear()->toDateString(),
            'created_by' => $administrator->getKey(),
        ]);
        $establishment = Establishment::query()->create([
            'company_id' => $company->getKey(),
            'code' => '001',
            'name' => 'Matriz',
            'address' => 'Quito, Ecuador',
            'is_main' => true,
            'is_active' => true,
        ]);
        Warehouse::query()->create([
            'company_id' => $company->getKey(),
            'establishment_id' => $establishment->getKey(),
            'code' => 'BOD-001',
            'name' => 'Bodega principal',
            'is_main' => true,
            'is_active' => true,
            'allow_negative_stock' => false,
        ]);

        $this->actingAs($administrator);

        return $administrator;
    }
}
