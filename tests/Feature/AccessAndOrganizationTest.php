<?php

namespace Tests\Feature;

use App\Domain\Access\Models\Role;
use App\Domain\Organization\Models\Company;
use App\Domain\Tax\Services\CompanyTaxPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessAndOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administration_screens_render_for_the_administrator(): void
    {
        $this->signInConfiguredAdministrator();

        foreach ([
            'settings.company.edit',
            'establishments.index',
            'warehouses.index',
            'users.index',
            'roles.index',
            'catalogs.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_an_administrator_can_create_roles_and_users(): void
    {
        $this->signInConfiguredAdministrator();

        $this->post(route('roles.store'), [
            'name' => 'Cajero',
            'description' => 'Operación de ventas y caja',
            'permissions' => ['dashboard.view', 'sales.manage', 'cash.manage'],
        ])->assertSessionHas('success');

        $role = Role::query()->where('slug', 'cajero')->firstOrFail();

        $this->post(route('users.store'), [
            'name' => 'Carlos Cajero',
            'email' => 'carlos@example.test',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'role_ids' => [$role->getKey()],
        ])->assertSessionHas('success');

        $user = User::query()->where('email', 'carlos@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('cajero'));
        $this->assertTrue($user->hasPermission('sales.manage'));
        $this->assertFalse($user->hasPermission('users.manage'));

        $this->put(route('users.update', $user), [
            'name' => 'Carlos Operador',
            'email' => 'carlos@example.test',
            'phone' => '0999999999',
            'role_ids' => [$role->getKey()],
        ])->assertSessionHas('success');

        $this->assertSame('Carlos Operador', $user->fresh()->name);
    }

    public function test_role_permissions_are_enforced_server_side(): void
    {
        $this->signInConfiguredAdministrator();
        $role = Role::query()->create([
            'name' => 'Consulta',
            'slug' => 'consulta',
            'description' => 'Solo resumen',
            'permissions' => ['dashboard.view'],
            'is_system' => false,
        ]);
        $user = User::query()->create([
            'name' => 'Usuario Consulta',
            'email' => 'consulta@example.test',
            'password' => 'SecurePass123',
            'is_active' => true,
        ]);
        $user->roles()->attach($role);
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('modules.show', 'ventas'))->assertForbidden();
    }

    public function test_an_administrator_can_add_an_establishment_and_warehouse(): void
    {
        $this->signInConfiguredAdministrator();
        $company = Company::query()->firstOrFail();

        $this->post(route('establishments.store'), [
            'code' => '002',
            'name' => 'Sucursal Norte',
            'address' => 'Norte de Quito',
            'is_active' => '1',
        ])->assertSessionHas('success');

        $establishment = $company->establishments()->where('code', '002')->firstOrFail();

        $this->post(route('warehouses.store'), [
            'establishment_id' => $establishment->getKey(),
            'code' => 'bod-norte',
            'name' => 'Bodega norte',
            'is_active' => '1',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('warehouses', [
            'company_id' => $company->getKey(),
            'establishment_id' => $establishment->getKey(),
            'code' => 'BOD-NORTE',
        ]);
    }

    public function test_rimpe_popular_profile_controls_vat_and_plastic_bag_ice_rules(): void
    {
        $this->signInConfiguredAdministrator();
        $company = Company::query()->firstOrFail();
        $taxPolicy = app(CompanyTaxPolicy::class);

        $this->assertTrue($company->isRimpePopularBusiness());
        $this->assertFalse($taxPolicy->shouldBreakDownVat($company));
        $this->assertFalse($taxPolicy->shouldCalculateProductIce($company));
        $this->assertFalse($taxPolicy->appliesPlasticBagIce($company));

        $this->get(route('settings.company.edit'))
            ->assertOk()
            ->assertSee('ICE a fundas plásticas')
            ->assertSee('No aplica');

        $company->taxProfiles()->firstOrFail()->update(['is_franchise' => true]);

        $this->assertTrue($taxPolicy->appliesPlasticBagIce($company));
    }

    public function test_rimpe_popular_cannot_be_scheduled_with_accounting_required(): void
    {
        $this->signInConfiguredAdministrator();

        $this->post(route('settings.company-tax-profiles.store'), [
            'tax_regime' => 'rimpe_popular_business',
            'effective_from' => now()->addDay()->toDateString(),
            'accounting_required' => '1',
        ])->assertSessionHasErrors('accounting_required');

        $this->assertSame(1, Company::query()->firstOrFail()->taxProfiles()->count());
    }

    public function test_the_only_active_administrator_cannot_be_deactivated(): void
    {
        $administrator = $this->signInConfiguredAdministrator();
        $managerRole = Role::query()->create([
            'name' => 'Gestor de usuarios',
            'slug' => 'gestor-usuarios',
            'description' => 'Gestiona cuentas sin ser administrador',
            'permissions' => ['users.manage'],
            'is_system' => false,
        ]);
        $manager = User::query()->create([
            'name' => 'Gestor de usuarios',
            'email' => 'manager@example.test',
            'password' => 'SecurePass123',
            'is_active' => true,
        ]);
        $manager->roles()->attach($managerRole);
        $this->actingAs($manager);

        $this->patch(route('users.toggle-status', $administrator))->assertSessionHasErrors('user');
        $this->assertTrue($administrator->fresh()->is_active);
    }
}
