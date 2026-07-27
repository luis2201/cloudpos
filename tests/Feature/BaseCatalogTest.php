<?php

namespace Tests\Feature;

use App\Domain\Access\Models\Role;
use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\MeasurementUnit;
use App\Domain\Catalog\Models\PaymentMethod;
use App\Domain\Catalog\Models\Presentation;
use App\Models\User;
use Database\Seeders\BaseCatalogSeeder;
use Database\Seeders\ProductCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_catalog_seed_is_idempotent_and_loads_official_payment_codes(): void
    {
        $this->seed(BaseCatalogSeeder::class);
        $this->seed(BaseCatalogSeeder::class);

        $this->assertSame(5, MeasurementUnit::query()->count());
        $this->assertSame(14, Presentation::query()->count());
        $this->assertSame(8, PaymentMethod::query()->count());
        $this->assertEqualsCanonicalizing(
            array_map('strval', array_keys(PaymentMethod::SRI_CODES)),
            PaymentMethod::query()->pluck('sri_code')->all(),
        );
        $this->assertTrue(PaymentMethod::query()->where('sri_code', '01')->firstOrFail()->affects_cash);
    }

    public function test_an_administrator_can_render_every_catalog_section(): void
    {
        $this->signInConfiguredAdministrator();
        $this->seed(BaseCatalogSeeder::class);

        foreach (['categories', 'brands', 'units', 'presentations', 'payment-methods'] as $section) {
            $response = $this->get(route('catalogs.index', ['section' => $section]))
                ->assertOk()
                ->assertSee('Catálogo base');

            if ($section === 'presentations') {
                $response->assertSee('Conversión')->assertSee('Presentaciones');
            }
        }
    }

    public function test_an_administrator_can_create_every_base_catalog_record(): void
    {
        $this->signInConfiguredAdministrator();
        $this->seed(BaseCatalogSeeder::class);

        $this->post(route('catalogs.categories.store'), [
            'code' => 'licores',
            'name' => 'Licores',
            'description' => 'Bebidas espirituosas',
            'is_active' => '1',
        ])->assertSessionHas('success');
        $category = Category::query()->where('code', 'LICORES')->firstOrFail();

        $this->post(route('catalogs.categories.store'), [
            'parent_id' => $category->getKey(),
            'code' => 'scotch-demo',
            'name' => 'Scotch de prueba',
            'is_active' => '1',
        ])->assertSessionHas('success');

        $this->post(route('catalogs.brands.store'), [
            'code' => 'marca-demo',
            'name' => 'Marca Demo',
            'is_active' => '1',
        ])->assertSessionHas('success');

        $this->post(route('catalogs.units.store'), [
            'code' => 'cl',
            'name' => 'Centilitro',
            'symbol' => 'cl',
            'dimension' => 'volume',
            'allows_decimals' => '1',
            'is_active' => '1',
        ])->assertSessionHas('success');
        $unit = MeasurementUnit::query()->where('code', 'CL')->firstOrFail();

        $this->post(route('catalogs.presentations.store'), [
            'code' => 'bot-70cl',
            'name' => 'Botella 70 cl',
            'quantity' => '70',
            'measurement_unit_id' => $unit->getKey(),
            'is_active' => '1',
        ])->assertSessionHas('success');

        $this->post(route('catalogs.payment-methods.store'), [
            'code' => 'transfer-pichincha',
            'name' => 'Transferencia Banco Pichincha',
            'sri_code' => '20',
            'requires_reference' => '1',
            'is_active' => '1',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('catalog_categories', ['code' => 'SCOTCH-DEMO', 'parent_id' => $category->getKey()]);
        $this->assertDatabaseHas('brands', ['code' => 'MARCA-DEMO', 'name' => 'Marca Demo']);
        $this->assertDatabaseHas('presentations', ['code' => 'BOT-70CL', 'measurement_unit_id' => $unit->getKey()]);
        $this->assertDatabaseHas('payment_methods', ['code' => 'TRANSFER-PICHINCHA', 'sri_code' => '20', 'is_system' => false]);
    }

    public function test_category_hierarchy_cannot_create_a_cycle(): void
    {
        $this->signInConfiguredAdministrator();
        $parent = Category::query()->create(['code' => 'PADRE', 'name' => 'Padre', 'is_active' => true]);
        $child = Category::query()->create(['parent_id' => $parent->getKey(), 'code' => 'HIJA', 'name' => 'Hija', 'is_active' => true]);

        $this->put(route('catalogs.categories.update', $parent), [
            'parent_id' => $child->getKey(),
            'code' => 'PADRE',
            'name' => 'Padre',
            'is_active' => '1',
        ])->assertSessionHasErrors('parent_id');

        $this->assertNull($parent->fresh()->parent_id);
    }

    public function test_a_unit_with_active_presentations_cannot_be_deactivated(): void
    {
        $this->signInConfiguredAdministrator();
        $this->seed(BaseCatalogSeeder::class);
        $unit = MeasurementUnit::query()->where('code', 'UND')->firstOrFail();

        $this->put(route('catalogs.units.update', $unit), [
            'code' => $unit->code,
            'name' => $unit->name,
            'symbol' => $unit->symbol,
            'dimension' => $unit->dimension,
        ])->assertSessionHasErrors('is_active');

        $this->assertTrue($unit->fresh()->is_active);
    }

    public function test_a_used_presentation_keeps_its_inventory_conversion(): void
    {
        $this->signInConfiguredAdministrator();
        $this->seed([BaseCatalogSeeder::class, ProductCatalogSeeder::class]);
        $presentation = Presentation::query()->where('code', 'CAJ-12')->firstOrFail();

        $this->put(route('catalogs.presentations.update', $presentation), [
            'code' => $presentation->code,
            'name' => $presentation->name,
            'quantity' => '24',
            'measurement_unit_id' => $presentation->measurement_unit_id,
            'is_active' => '1',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame('12.0000', $presentation->fresh()->quantity);

        $this->put(route('catalogs.presentations.update', $presentation), [
            'code' => $presentation->code,
            'name' => 'Caja protegida de 12 unidades',
            'quantity' => '12',
            'measurement_unit_id' => $presentation->measurement_unit_id,
            'is_active' => '1',
        ])->assertSessionHas('success');

        $this->assertSame('Caja protegida de 12 unidades', $presentation->fresh()->name);
    }

    public function test_official_payment_identity_is_protected_but_operational_flags_can_change(): void
    {
        $this->signInConfiguredAdministrator();
        $this->seed(BaseCatalogSeeder::class);
        $paymentMethod = PaymentMethod::query()->where('sri_code', '01')->firstOrFail();

        $this->put(route('catalogs.payment-methods.update', $paymentMethod), [
            'code' => 'ALTERADO',
            'sri_code' => '20',
            'name' => 'Nombre alterado',
            'requires_reference' => '1',
            'is_active' => '1',
        ])->assertSessionHas('success');

        $paymentMethod->refresh();
        $this->assertSame('SRI-01', $paymentMethod->code);
        $this->assertSame('01', $paymentMethod->sri_code);
        $this->assertSame(PaymentMethod::SRI_CODES['01'], $paymentMethod->name);
        $this->assertTrue($paymentMethod->requires_reference);
        $this->assertFalse($paymentMethod->affects_cash);
    }

    public function test_catalog_permission_is_enforced_server_side(): void
    {
        $this->signInConfiguredAdministrator();
        $role = Role::query()->create([
            'name' => 'Solo resumen',
            'slug' => 'solo-resumen-catalogo',
            'permissions' => ['dashboard.view'],
            'is_system' => false,
        ]);
        $user = User::query()->create([
            'name' => 'Usuario sin catálogo',
            'email' => 'sin-catalogo@example.test',
            'password' => 'SecurePass123',
            'is_active' => true,
        ]);
        $user->roles()->attach($role);
        $this->actingAs($user);

        $this->get(route('catalogs.index'))->assertForbidden();
        $this->post(route('catalogs.brands.store'), [
            'code' => 'NO',
            'name' => 'No autorizada',
        ])->assertForbidden();
        $this->assertSame(0, Brand::query()->count());
    }
}
