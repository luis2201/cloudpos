<?php

namespace Tests\Feature;

use App\Domain\Access\Models\Role;
use App\Domain\Organization\Models\Company;
use App\Domain\Organization\Models\Establishment;
use App\Domain\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_empty_installation_requires_the_first_administrator(): void
    {
        $this->get(route('login'))->assertRedirect(route('setup.admin.create'));

        $this->get(route('setup.admin.create'))
            ->assertOk()
            ->assertSee('Crea el administrador principal');
    }

    public function test_the_first_user_is_an_administrator_and_the_steps_cannot_be_skipped(): void
    {
        $response = $this->post(route('setup.admin.store'), [
            'name' => 'Ana Administradora',
            'email' => 'ana@example.test',
            'phone' => '0999999999',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertRedirect(route('setup.company.create'));
        $this->assertAuthenticated();

        $user = User::query()->firstOrFail();
        $this->assertTrue($user->hasRole(Role::ADMINISTRATOR));
        $this->assertSame(1, User::query()->count());

        $this->get(route('dashboard'))->assertRedirect(route('setup.company.create'));
        $this->get(route('setup.warehouse.create'))->assertRedirect(route('setup.company.create'));
    }

    public function test_the_complete_setup_flow_creates_the_company_matrix_and_main_warehouse(): void
    {
        $this->post(route('setup.admin.store'), [
            'name' => 'Ana Administradora',
            'email' => 'ana@example.test',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ])->assertRedirect(route('setup.company.create'));

        $this->post(route('setup.company.store'), [
            'ruc' => '1712345678001',
            'legal_name' => 'Ana Administradora',
            'trade_name' => 'Licorería Central',
            'email' => 'empresa@example.test',
            'phone' => '022222222',
            'address' => 'Av. Principal 123, Quito',
        ])->assertRedirect(route('setup.establishment.create'));

        $this->post(route('setup.establishment.store'), [
            'code' => '001',
            'name' => 'Matriz',
            'trade_name' => 'Licorería Central',
            'address' => 'Av. Principal 123, Quito',
        ])->assertRedirect(route('setup.warehouse.create'));

        $this->post(route('setup.warehouse.store'), [
            'code' => 'bod-principal',
            'name' => 'Bodega principal',
            'address' => 'Av. Principal 123, Quito',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('companies', [
            'ruc' => '1712345678001',
            'currency' => 'USD',
            'taxpayer_type' => 'natural_person',
            'rimpe_category' => 'popular_business',
            'accounting_required' => false,
            'ice_taxpayer_role' => 'retailer',
            'is_franchise' => false,
        ]);
        $this->assertDatabaseHas('establishments', ['code' => '001', 'is_main' => true]);
        $this->assertDatabaseHas('warehouses', ['code' => 'BOD-PRINCIPAL', 'is_main' => true, 'allow_negative_stock' => false]);
        $this->assertDatabaseHas('company_tax_profiles', [
            'tax_regime' => 'rimpe_popular_business',
            'taxpayer_type' => 'natural_person',
            'accounting_required' => false,
        ]);
        $this->get(route('dashboard'))->assertOk()->assertSee('Configuración principal completada');
    }

    public function test_initial_administrator_registration_is_closed_after_the_first_user(): void
    {
        $this->signInConfiguredAdministrator();
        $this->post(route('logout'));

        $this->get(route('setup.admin.create'))->assertRedirect(route('login'));
        $this->assertSame(1, User::query()->count());
    }

    public function test_ruc_requires_exactly_thirteen_digits(): void
    {
        $this->post(route('setup.admin.store'), [
            'name' => 'Ana Administradora',
            'email' => 'ana@example.test',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $this->post(route('setup.company.store'), [
            'ruc' => '1234',
            'legal_name' => 'Empresa de prueba',
            'taxpayer_type' => 'private_company',
            'address' => 'Quito',
        ])->assertSessionHasErrors('ruc');

        $this->assertSame(0, Company::query()->count());
        $this->assertSame(0, Establishment::query()->count());
        $this->assertSame(0, Warehouse::query()->count());
    }
}
