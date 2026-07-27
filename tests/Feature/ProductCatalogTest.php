<?php

namespace Tests\Feature;

use App\Domain\Access\Models\Role;
use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Presentation;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductPackage;
use App\Domain\Catalog\Services\InventoryUnitConverter;
use App\Domain\Catalog\Services\ProductBarcodeService;
use App\Models\User;
use Database\Seeders\BaseCatalogSeeder;
use Database\Seeders\ProductCatalogSeeder;
use Database\Seeders\TaxRateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_seed_adds_liquor_categories_and_brands_idempotently(): void
    {
        $this->seed(BaseCatalogSeeder::class);
        $this->seed(BaseCatalogSeeder::class);

        $this->assertSame(21, Category::query()->count());
        $this->assertSame(31, Brand::query()->count());
        $this->assertDatabaseHas('catalog_categories', ['code' => 'WHISKY', 'name' => 'Whisky']);
        $this->assertDatabaseHas('catalog_categories', ['code' => 'CERVEZA', 'name' => 'Cervezas']);
        $this->assertDatabaseHas('brands', ['code' => 'PILSENER', 'name' => 'Pilsener']);
        $this->assertDatabaseHas('brands', ['code' => 'JOHNNIE-WALKER', 'name' => 'Johnnie Walker']);
    }

    public function test_product_seed_loads_idempotent_net_prices_and_internal_barcodes(): void
    {
        $this->seed([BaseCatalogSeeder::class, ProductCatalogSeeder::class]);
        $editedPrice = Product::query()->where('code', 'PILSENER-330-BOT')->firstOrFail()->basePackage;
        $editedPrice->update(['price_before_tax' => '0.9100']);
        $this->seed(ProductCatalogSeeder::class);

        $this->assertSame(30, Product::query()->count());
        $this->assertSame(69, ProductPackage::query()->count());
        $this->assertSame(30, ProductPackage::query()->where('is_base', true)->count());
        $this->assertSame(0, ProductPackage::query()->whereNotNull('barcode')->count());
        $this->assertSame(69, ProductPackage::query()->whereNotNull('internal_barcode')->count());
        $this->assertSame(69, ProductPackage::query()->where('price_before_tax', '>', 0)->count());
        $this->assertSame('0.9100', $editedPrice->fresh()->price_before_tax);
        $this->assertTrue(ProductPackage::query()->get()->every(
            fn (ProductPackage $package) => preg_match('/^CP\d{10}$/', $package->internal_barcode) === 1,
        ));
        $this->assertDatabaseHas('products', [
            'code' => 'JW-BLACK-LABEL-750',
            'category_id' => Category::query()->where('code', 'WHISKY')->firstOrFail()->getKey(),
            'brand_id' => Brand::query()->where('code', 'JOHNNIE-WALKER')->firstOrFail()->getKey(),
            'ice_treatment' => 'alcoholic_beverage',
        ]);

        $pilsener = Product::query()->where('code', 'PILSENER-330-BOT')->firstOrFail();
        $case = $pilsener->packages()->whereHas('presentation', fn ($query) => $query->where('code', 'CAJ-24'))->firstOrFail();

        $this->assertSame(24, $case->units_per_package);
        $this->assertSame('17.2800', $case->price_before_tax);
    }

    public function test_an_alcoholic_product_stores_its_base_price_and_upstream_ice_reference(): void
    {
        $this->signInConfiguredAdministrator();
        $this->seed([BaseCatalogSeeder::class, TaxRateSeeder::class]);

        $this->post(route('products.store'), $this->productPayload())
            ->assertRedirect();

        $product = Product::query()->where('code', 'WHISKY-DEMO-750')->firstOrFail();
        $package = $product->packages()->firstOrFail();

        $this->assertSame('general', $product->vat_treatment);
        $this->assertSame('alcoholic_beverage', $product->ice_treatment);
        $this->assertSame('40.00', $product->alcohol_by_volume);
        $this->assertSame('12.3456', $package->price_before_tax);
        $this->assertSame(1, $package->units_per_package);
        $this->assertTrue($package->is_base);
    }

    public function test_a_box_automatically_converts_to_its_number_of_inventory_units(): void
    {
        $this->signInConfiguredAdministrator();
        $this->seed(BaseCatalogSeeder::class);
        $this->post(route('products.store'), $this->productPayload());
        $product = Product::query()->where('code', 'WHISKY-DEMO-750')->firstOrFail();
        $box = Presentation::query()->where('code', 'CAJ-12')->firstOrFail();

        $this->post(route('products.packages.store', $product), [
            'presentation_id' => $box->getKey(),
            'barcode' => '7860000000010',
            'price_before_tax' => '120.0000',
            'is_active' => '1',
        ])->assertSessionHas('success');

        $package = ProductPackage::query()->where('product_id', $product->getKey())->where('presentation_id', $box->getKey())->firstOrFail();
        $this->assertSame(12, $package->units_per_package);
        $this->assertSame(60, app(InventoryUnitConverter::class)->convert($package, 5));
        $this->assertSame('120.0000', $package->price_before_tax);
    }

    public function test_manufacturer_gtin_is_validated_and_internal_code_is_printable(): void
    {
        $this->signInConfiguredAdministrator();
        $this->seed(BaseCatalogSeeder::class);
        $payload = $this->productPayload();
        $payload['barcode'] = '6291041500213';

        $this->post(route('products.store'), $payload)->assertRedirect();

        $product = Product::query()->where('code', 'WHISKY-DEMO-750')->firstOrFail();
        $package = $product->basePackage()->firstOrFail();
        $this->assertSame('6291041500213', $package->barcode);
        $this->assertMatchesRegularExpression('/^CP\d{10}$/', $package->internal_barcode);

        $this->get(route('products.packages.internal-barcode.svg', [$product, $package]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertSee('<svg', false);

        $invalidPayload = $this->productPayload();
        $invalidPayload['code'] = 'WHISKY-INVALID-GTIN';
        $invalidPayload['barcode'] = '6291041500214';

        $this->post(route('products.store'), $invalidPayload)
            ->assertSessionHasErrors('barcode');

        $this->assertTrue(app(ProductBarcodeService::class)->hasValidGtinCheckDigit('6291041500213'));
    }

    public function test_an_alcoholic_product_requires_alcohol_content(): void
    {
        $this->signInConfiguredAdministrator();
        $this->seed(BaseCatalogSeeder::class);
        $payload = $this->productPayload();
        unset($payload['alcohol_by_volume']);

        $this->post(route('products.store'), $payload)
            ->assertSessionHasErrors('alcohol_by_volume');

        $this->assertSame(0, Product::query()->count());
    }

    public function test_product_and_package_screens_render(): void
    {
        $this->signInConfiguredAdministrator();
        $this->seed([BaseCatalogSeeder::class, TaxRateSeeder::class]);
        $this->post(route('products.store'), $this->productPayload());
        $product = Product::query()->firstOrFail();

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('RIMPE Negocio Popular')
            ->assertSee('ICE no se suma')
            ->assertSee('Whisky Demo 750 ml');
        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Sin desglose de IVA')
            ->assertSee('Control por unidad')
            ->assertSee('no maneja fecha de caducidad');
    }

    public function test_a_quantity_presentation_cannot_contain_fractional_units(): void
    {
        $this->signInConfiguredAdministrator();
        $this->seed(BaseCatalogSeeder::class);
        $unit = Presentation::query()->where('code', 'UND-1')->firstOrFail()->measurementUnit;

        $this->post(route('catalogs.presentations.store'), [
            'code' => 'MEDIA-UNIDAD',
            'name' => 'Media unidad',
            'quantity' => '1.5',
            'measurement_unit_id' => $unit->getKey(),
            'is_active' => '1',
        ])->assertSessionHasErrors('quantity');
    }

    public function test_product_permission_is_enforced_server_side(): void
    {
        $this->signInConfiguredAdministrator();
        $role = Role::query()->create([
            'name' => 'Sin productos',
            'slug' => 'sin-productos',
            'permissions' => ['dashboard.view'],
            'is_system' => false,
        ]);
        $user = User::query()->create([
            'name' => 'Usuario sin productos',
            'email' => 'sin-productos@example.test',
            'password' => 'SecurePass123',
            'is_active' => true,
        ]);
        $user->roles()->attach($role);
        $this->actingAs($user);

        $this->get(route('products.index'))->assertForbidden();
        $this->post(route('products.store'), [])->assertForbidden();
    }

    /** @return array<string, mixed> */
    private function productPayload(): array
    {
        return [
            'category_id' => Category::query()->where('code', 'WHISKY')->firstOrFail()->getKey(),
            'brand_id' => Brand::query()->where('code', 'JOHNNIE-WALKER')->firstOrFail()->getKey(),
            'code' => 'whisky-demo-750',
            'name' => 'Whisky Demo 750 ml',
            'description' => 'Producto de prueba',
            'product_kind' => 'alcoholic_beverage',
            'alcohol_by_volume' => '40.00',
            'vat_treatment' => 'general',
            'ice_treatment' => 'alcoholic_beverage',
            'ice_code' => 'ICE-DEMO',
            'presentation_id' => Presentation::query()->where('code', 'BOT-750ML')->firstOrFail()->getKey(),
            'barcode' => '7860000000003',
            'price_before_tax' => '12.3456',
            'is_active' => '1',
        ];
    }
}
